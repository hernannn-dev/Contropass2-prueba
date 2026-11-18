<?php
// ==========================================================================
// SCRIPT DE LIMPIEZA AUTOMÁTICA DE FICHAS EXPIRADAS (ETAPA PRODUCTIVA)
// ==========================================================================
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    require_once __DIR__ . '/../seguridad.php';
    validarAccesoApi(true);
}

try {
    if (!isset($db) || !$db) {
        $raizProyecto = dirname(__DIR__, 2);
        if (file_exists($raizProyecto . '/config/conexion.php')) {
            require_once $raizProyecto . '/config/conexion.php';
            $db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);
        }
    }

    if ($db) {
        // Criterio basado en el estado o en la fecha de finalización de programa (etapa productiva)
        $sqlCheck = "SELECT numero_ficha FROM fichas 
                     WHERE estado_ficha IN ('FINALIZADA', 'CANCELADA') 
                        OR (fecha_fin_programa IS NOT NULL AND fecha_fin_programa != '0000-00-00' AND fecha_fin_programa < CURDATE())";
        
        $stmt = $db->prepare($sqlCheck);
        $stmt->execute();
        $fichasExpiradas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($fichasExpiradas)) {
            foreach ($fichasExpiradas as $numFicha) {
                $db->beginTransaction();

                // 1. Identificar aprendices de esta ficha que tienen equipos con estado ADENTRO
                $stmtAdentro = $db->prepare("
                    SELECT DISTINCT u.id 
                    FROM usuarios u
                    JOIN registros_acceso ra ON ra.usuario_id = u.id
                    WHERE u.ficha = :num AND u.rol_id = 3 AND ra.estado = 'ADENTRO'
                ");
                $stmtAdentro->execute([':num' => $numFicha]);
                $idsProtegidos = $stmtAdentro->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($idsProtegidos)) {
                    // Si hay aprendices con equipos adentro, eliminamos SOLO a los aprendices libres de equipos
                    $placeholders = implode(',', array_fill(0, count($idsProtegidos), '?'));
                    $paramsDelU = array_merge([$numFicha], $idsProtegidos);

                    $stmtDelU = $db->prepare("DELETE FROM usuarios WHERE ficha = ? AND rol_id = 3 AND id NOT IN ($placeholders)");
                    $stmtDelU->execute($paramsDelU);
                } else {
                    // Si nadie tiene equipos adentro, eliminamos a todos los aprendices y a la tabla de aprendices
                    $stmtDelU = $db->prepare("DELETE FROM usuarios WHERE ficha = :num AND rol_id = 3");
                    $stmtDelU->execute([':num' => $numFicha]);

                    $stmtDelA = $db->prepare("DELETE FROM aprendices WHERE numero_ficha = :num");
                    $stmtDelA->execute([':num' => $numFicha]);
                }

                // 2. Verificar si aún quedan aprendices con equipos adentro en esta ficha
                $stmtRestantes = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE ficha = :num AND rol_id = 3");
                $stmtRestantes->execute([':num' => $numFicha]);
                $quedanAprendices = $stmtRestantes->fetchColumn();

                if ($quedanAprendices == 0) {
                    // Si ya no quedan aprendices, eliminar la ficha definitivamente
                    $stmtDelF = $db->prepare("DELETE FROM fichas WHERE numero_ficha = :num");
                    $stmtDelF->execute([':num' => $numFicha]);
                }

                $db->commit();
            }
        }
    }
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error en limpieza automática de fichas por etapa productiva: " . $e->getMessage());
}
?>