<?php
// api/porteria/obtener_dispositivos_activos.php
header('Content-Type: application/json; charset=utf-8');

// 🟢 Blindaje de seguridad centralizado
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true);

require_once '../../config/conexion.php';

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$page   = intval($data['page']   ?? $_GET['page']   ?? 1);
$search = trim($data['search'] ?? $_GET['search'] ?? '');
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);
    if (!$db) {
        throw new Exception("No hay conexión activa a la base de datos.");
    }

    $where = "ra.estado = 'ADENTRO' AND ra.equipo_id IS NOT NULL";
    $params = [];

    if (!empty($search)) {
        // 🟢 Corrección: Se usan marcadores únicos para evitar errores de PDO al repetir el mismo parámetro
        $where .= " AND (e.serial LIKE :search1 OR COALESCE(NULLIF(TRIM(CONCAT(u.nombres, ' ', u.apellidos)), ''), ra.usuario_nombre) LIKE :search2 OR e.tipo_equipo LIKE :search3)";
        $params[':search1'] = "%$search%";
        $params[':search2'] = "%$search%";
        $params[':search3'] = "%$search%";
    }

    $sqlTotal = "SELECT COUNT(*) as total 
                 FROM registros_acceso ra 
                 JOIN equipos e ON ra.equipo_id = e.id 
                 LEFT JOIN usuarios u ON ra.usuario_id = u.id 
                 WHERE $where";
    $stmtTotal = $db->prepare($sqlTotal);
    $stmtTotal->execute($params);
    $totalRow = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    $totalElementos = $totalRow ? intval($totalRow['total']) : 0;
    $totalPaginas = ceil($totalElementos / $limit) ?: 1;

    $sql = "SELECT 
                COALESCE(NULLIF(e.tipo_equipo, ''), 'Portátil') AS tipo_elemento, 
                e.serial, 
                COALESCE(NULLIF(TRIM(CONCAT(u.nombres, ' ', u.apellidos)), ''), ra.usuario_nombre, 'Usuario eliminado') AS propietario, 
                ra.fecha_ingreso, 
                CONCAT(g.nombres, ' ', g.apellidos) AS guardia_responsable
            FROM registros_acceso ra
            JOIN equipos e ON ra.equipo_id = e.id
            LEFT JOIN usuarios u ON ra.usuario_id = u.id
            LEFT JOIN usuarios g ON ra.vigilante_entrada_id = g.id
            WHERE $where
            ORDER BY ra.id DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => true,
        'totalElementos' => $totalElementos,
        'totalPaginas' => $totalPaginas,
        'data' => $resultados
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'mensaje' => $e->getMessage(),
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
}
?>