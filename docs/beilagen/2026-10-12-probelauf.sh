#!/bin/bash
# Probelauf zum Befund vom 12.10.2026 (docs/BEFUND-2026-10-12-webuntis-client-bestand.md).
# Baut einen Probebaum außerhalb des Repos: Moduldatei extractors.php
# unverändert ("ohne") bzw. mit je einer Ergänzung angehängt, und lässt
# die beiden roten Testdateien dagegen laufen. FASST DAS REPO NICHT AN.
# Aufruf: bash docs/beilagen/2026-10-12-probelauf.sh
set -u
export LC_ALL=C
Q="$(cd "$(dirname "$0")/../.." && pwd)"
P="${PROBEBAUM:-$(mktemp -d)}"
echo "Quelle: $Q"
echo "Probebaum: $P"
for v in ohne 1.2.0 1.6.0; do
  d="$P/$v"; rm -rf "$d"; mkdir -p "$d/src" "$d/tests"
  cp "$Q"/tests/extractors_*_test.php "$d/tests/"
  cp "$Q/src/extractors.php" "$d/src/"
  if [ "$v" != ohne ]; then
    # alles ab der ersten "/**"-Zeile anhängen, wie EINFUEGEN.md es verlangt
    awk 'f||/^\/\*\*/{f=1; print}' "$Q/src/extractors_ergaenzung_v$v.php" >> "$d/src/extractors.php"
  fi
  echo "[$v] Funktionen in extractors.php: $(grep -c '^function ' "$d/src/extractors.php")"
  for t in "$d"/tests/*.php; do
    php "$t" > "$t.out" 2>&1
    rc=$?
    echo "[$v] $(basename "$t"): exit=$rc ok=$(grep -c '✓' "$t.out") rot=$(grep -c '✗' "$t.out") fatal=$(grep -c 'Fatal' "$t.out")"
  done
done
