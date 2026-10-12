#!/bin/bash
# ============================================================
# tests/alle.sh – startet ALLE Testdateien, jede einzeln.
#
# Die Dateien werden ERMITTELT (jede *.php in diesem Verzeichnis),
# nicht aufgezählt: Eine fünfte Datei läuft mit, ohne dass hier
# jemand eine Zeile ergänzt. Anlass: Bis v2.0.0 startete kein Skript
# alle Suiten, die README nannte nur die grünen – zwei Dateien
# brachen sechs Wochen lang mit einem Fatal, ohne dass es auffiel.
#
# Rot ist:   ein Exit-Code ungleich 0,
#            eine Datei ohne erkennbare Prüfung (null Funde sind
#            ein Fehler, kein Ergebnis),
#            ein Verzeichnis ohne Testdatei.
#
# Aufruf:    bash tests/alle.sh            alle laufen lassen
#            bash tests/alle.sh --liste    nur die ermittelten Dateien
# Umgebung:  TESTVERZEICHNIS=…             anderes Verzeichnis (für
#                                          tests/sammelskript_test.php)
# ============================================================
set -u
export LC_ALL=C

VERZ="${TESTVERZEICHNIS:-$(cd "$(dirname "$0")" && pwd)}"
shopt -s nullglob
dateien=("$VERZ"/*.php)

if [ "${#dateien[@]}" -eq 0 ]; then
    echo "ROT: keine Testdateien in $VERZ gefunden"
    exit 1
fi

if [ "${1:-}" = "--liste" ]; then
    for d in "${dateien[@]}"; do echo "$d"; done
    echo "${#dateien[@]} Testdateien"
    exit 0
fi

rot=0
summeOk=0
summeRot=0
for d in "${dateien[@]}"; do
    aus="$(php "$d" 2>&1)"
    rc=$?
    # Zwei Ausgabeformen sind im Bestand: "OK  …"/"FEHLER  …" und
    # "  ✓ …"/"  ✗ …". Ein Fatal zählt als EINE rote Prüfung – PHP
    # gibt ihn auf der Kommandozeile zweimal aus.
    ok=$(printf '%s\n' "$aus" | grep -c -E '^OK |^  ✓ ' || true)
    fe=$(printf '%s\n' "$aus" | grep -c -E '^FEHLER |^  ✗ ' || true)
    if printf '%s\n' "$aus" | grep -q 'Fatal error'; then fe=$((fe + 1)); fi
    summeOk=$((summeOk + ok))
    summeRot=$((summeRot + fe))
    befund="grün"
    if [ "$rc" -ne 0 ]; then
        befund="ROT (Exit $rc)"
        rot=$((rot + 1))
    elif [ "$ok" -eq 0 ]; then
        befund="ROT (keine Prüfung erkannt)"
        rot=$((rot + 1))
    fi
    printf '%-40s %4d ok %4d rot   %s\n' "$(basename "$d")" "$ok" "$fe" "$befund"
done

echo "${#dateien[@]} Testdateien, $summeOk Prüfungen bestanden, $summeRot rot"
if [ "$rot" -ne 0 ]; then
    echo "ROT: $rot von ${#dateien[@]} Dateien"
    exit 1
fi
echo "ALLE DATEIEN GRÜN"
exit 0
