#!/usr/bin/env python3
"""
dump_project.py — Volcado de texto del proyecto ControlPass para depuración.
"""
import argparse
import fnmatch
import os
import sys
from datetime import datetime
from pathlib import Path

# Excluir carpetas pesadas o innecesarias
EXCLUDE_DIRS = {
    ".git", ".svn", ".hg",
    "__pycache__", ".pytest_cache",
    "node_modules", ".venv", "venv",
    ".idea", ".vscode",
    "vendor",        # <-- CRÍTICO: No leer dependencias de composer
    "uploads",       # <-- CRÍTICO: No leer imágenes subidas (privacidad)
    "imagenes",      # <-- Excluir imágenes para no intentar leer binarios
}

EXCLUDE_FILES = [
    "*.pyc", "*.pyo", "*.log",
    "debug_dump_*.txt", "*.dump.txt",
    "*.sqlite", "*.db",
    ".DS_Store", "Thumbs.db",
    ".env", "*.env",
    "*.png", "*.jpg", "*.jpeg", "*.gif", "*.webp", "*.ico", # Ignorar imágenes
    "composer.lock", # El lock es muy largo, no aporta al debug
]

TEXT_EXTS = {
    "py", "html", "htm", "php", "css", "js", "json", "sql", "md", "txt", "sh",
}

def is_excluded_dir(name: str) -> bool:
    return name in EXCLUDE_DIRS

def is_excluded_file(name: str) -> bool:
    return any(fnmatch.fnmatch(name, pat) for pat in EXCLUDE_FILES)

def build_tree(root: Path, out, skip: Path, max_depth: int = 10):
    def walk(dir_path: Path, prefix: str, depth: int):
        if depth > max_depth: return
        try:
            entries = sorted(dir_path.iterdir(), key=lambda p: (not p.is_dir(), p.name.lower()))
        except PermissionError: return
        
        entries = [
            e for e in entries
            if e.resolve() != skip
            and not (e.is_dir() and is_excluded_dir(e.name))
            and not (e.is_file() and is_excluded_file(e.name))
        ]
        for i, entry in enumerate(entries):
            is_last = i == len(entries) - 1
            connector = "└── " if is_last else "├── "
            suffix = "/" if entry.is_dir() else ""
            out.write(f"{prefix}{connector}{entry.name}{suffix}\n")
            if entry.is_dir():
                extension = "    " if is_last else "│   "
                walk(entry, prefix + extension, depth + 1)

    out.write(f"{root.name}/\n")
    walk(root, "", 0)

def collect_files(root: Path, skip: Path):
    for dirpath, dirnames, filenames in os.walk(root):
        dirnames[:] = sorted(d for d in dirnames if not is_excluded_dir(d))
        for fname in sorted(filenames):
            if is_excluded_file(fname): continue
            full = (Path(dirpath) / fname).resolve()
            if full == skip: continue
            yield full

def dump_file(path: Path, root: Path, out):
    rel = path.relative_to(root)
    try:
        size = path.stat().st_size
        if size > 500 * 1024: # Ignorar archivos mayores a 500KB
            out.write(f"\n[FILE OMITIDO POR TAMAÑO: {rel} - {size} bytes]\n")
            return
        text = path.read_text(encoding="utf-8")
    except Exception as e:
        return

    out.write("\n" + "=" * 78 + "\n")
    out.write(f"FILE: {rel.as_posix()}\n")
    out.write("=" * 78 + "\n")
    out.write(text if text.endswith("\n") else text + "\n")

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("-o", "--output", help="Archivo de salida")
    args = ap.parse_args()

    root = Path(".").resolve()
    ts = datetime.now().strftime("%Y%m%d_%H%M%S")
    out_path = root / (args.output or f"debug_dump_{ts}.txt")

    with out_path.open("w", encoding="utf-8") as out:
        out.write(f"# VOLCADO DEL PROYECTO CONTROLPASS\n")
        out.write(f"# Fecha: {datetime.now().isoformat(timespec='seconds')}\n\n")

        out.write("#" * 78 + "\n# 1. ESTRUCTURA DEL PROYECTO\n" + "#" * 78 + "\n")
        build_tree(root, out, out_path)

        out.write("\n\n" + "#" * 78 + "\n# 2. CONTENIDO DE ARCHIVOS\n" + "#" * 78 + "\n")
        files = list(collect_files(root, out_path))
        for f in files:
            dump_file(f, root, out)

    print(f"✓ Volcado generado: {out_path.name}")

if __name__ == "__main__":
    main()
