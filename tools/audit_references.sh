#!/usr/bin/env bash
set -euo pipefail
ROOT="${1:-.}"
cd "$ROOT"
printf '%s\n' '=== ENDPOINTS REFERENCIADOS ==='
grep -RIn --exclude-dir=.git -E "(save_board\.php|get_board_db\.php|board\.php\?id|pages/|boards/)" . || true
printf '%s\n' '=== FUNÇÕES PHP DECLARADAS ==='
grep -RhoP --include='*.php' '(?m)^\s*(?:public\s+|private\s+|protected\s+)?function\s+\K[A-Za-z_][A-Za-z0-9_]*' . | sort | uniq -c | sort -k2
printf '%s\n' '=== POSSÍVEIS HELPERS PHP NÃO REFERENCIADOS ==='
while read -r fn; do
  count=$(grep -Rho --include='*.php' -E "\b${fn}\s*\(" . | wc -l)
  if [ "$count" -le 1 ]; then printf '%s (%s ocorrência)\n' "$fn" "$count"; fi
done < <(grep -RhoP --include='*.php' '(?m)^\s*(?:public\s+|private\s+|protected\s+)?function\s+\K[A-Za-z_][A-Za-z0-9_]*' . | sort -u)
printf '%s\n' '=== MÓDULOS JAVASCRIPT E ENDPOINTS CONFIGURÁVEIS ==='
grep -RIn --include='*.js' -E "(import |export |saveUrl|source|fetch\()" core assets/js || true
printf '%s\n' '=== ARTEFATOS LEGADOS ==='
printf 'pages_php='; find pages -maxdepth 1 -type f -name '*.php' 2>/dev/null | wc -l
printf 'board_json='; find boards -mindepth 2 -type f -name '*.json' 2>/dev/null | wc -l
