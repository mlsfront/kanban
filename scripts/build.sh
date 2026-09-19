#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)"
DIST_DIR="$PROJECT_ROOT/dist"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
ARCHIVE="$DIST_DIR/kanban_${TIMESTAMP}.zip"

mkdir -p "$DIST_DIR"

# O script está em scripts/, mas empacota o diretório pai,
# que é a raiz completa do projeto.
cd "$PROJECT_ROOT"

zip -qr "$ARCHIVE" . \
  -x './.git/*' \
  './.env*' \
     './legacy/*' \
     './logs/*' \
     './tests/*' \
     './docs/*' \
     './vendor/*' \
     './dist/*' \
  './.idea/*' \
  './.vscode/*' \
  './.DS_Store' \
  '*/.DS_Store' \
  './Thumbs.db' \
  '*/Thumbs.db' \
     './*.log' \
     '*/debug_log.txt' \
     './scripts/build.sh'

printf 'Arquivo criado: %s\n' "$ARCHIVE"