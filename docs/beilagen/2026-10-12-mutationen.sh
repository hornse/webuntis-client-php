#!/bin/bash
# ============================================================
# Mutationsserie zur Zusammenführung v2.0.0 (12.10.2026).
#
# Je Mutation: Sicherungskopie, Eingriff, WIRKUNGSNACHWEIS (das
# Muster muss vorher vorkommen und nachher die neue Form tragen),
# Lauf von tests/alle.sh, die erwartete rote Zeile muss erscheinen,
# Rücknahme AUS DER SICHERUNGSKOPIE (nie aus dem Index), dann
# Prüfsummenvergleich gegen den Sollstand.
#
# Am Ende: Prüfsummen ALLER Dateien unter src/ tests/ docs/ README.md
# gleich dem Stand vor der Serie.
#
# Aufruf: bash docs/beilagen/2026-10-12-mutationen.sh
# ============================================================
set -u
export LC_ALL=C
R="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$R" || exit 2
SICH="$(mktemp -d)"

stand() { find src tests docs README.md -type f -print0 | sort -z | xargs -0 shasum | shasum | cut -c1-16; }
VORHER="$(stand)"
echo "Sollstand vor der Serie: $VORHER"

nr=0; getroffen=0; verfehlt=0; kaputt=0

# mutiere ID DATEI ALT NEU ERWARTET [NICHT_ERWARTET]
#   ALT/NEU: wörtliche Zeichenketten. ERWARTET: Text, der in der Ausgabe
#   von alle.sh als rote Zeile stehen muss. NICHT_ERWARTET (optional):
#   Text, der NICHT rot werden darf (Trennschärfe).
mutiere() {
    local id="$1" datei="$2" alt="$3" neu="$4" erwartet="$5" nicht="${6:-}"
    nr=$((nr + 1))
    cp "$datei" "$SICH/orig"
    local soll; soll="$(shasum "$datei" | cut -c1-40)"
    local vor; vor="$(ALT="$alt" perl -0ne '$n = () = /\Q$ENV{ALT}\E/g; print $n' "$datei")"
    ALT="$alt" NEU="$neu" perl -0pi -e 's/\Q$ENV{ALT}\E/$ENV{NEU}/' "$datei"
    local nach; nach="$(NEU="$neu" perl -0ne '$n = () = /\Q$ENV{NEU}\E/g; print $n' "$datei")"
    if [ "$vor" -lt 1 ] || [ "$nach" -lt 1 ] || [ "$(shasum "$datei" | cut -c1-40)" = "$soll" ]; then
        echo "[$id] EINGRIFF NICHT ANGEKOMMEN (vorher $vor, nachher $nach) – zählt nicht"
        kaputt=$((kaputt + 1))
    else
        local aus rc
        aus="$(bash tests/alle.sh 2>&1; for f in tests/*.php; do php "$f" 2>&1; done)"
        # Der Exit-Code kommt aus den DIREKTEN Läufen, nicht aus alle.sh:
        # Ist alle.sh selbst mutiert (M9-M11), wäre es sonst sein eigener
        # Richter (M10 lief im ersten Durchgang genau daran vorbei).
        rc=0; for f in tests/*.php; do php "$f" > /dev/null 2>&1 || rc=1; done
        if [ "$rc" -ne 0 ] && printf '%s\n' "$aus" | grep -F -q -- "$erwartet"; then
            if [ -n "$nicht" ] && printf '%s\n' "$aus" | grep -E '^FEHLER|✗' | grep -F -q -- "$nicht"; then
                echo "[$id] getroffen, aber NICHT TRENNSCHARF: auch rot: $nicht"
                verfehlt=$((verfehlt + 1))
            else
                echo "[$id] getroffen (Exit $rc): $(printf '%s\n' "$aus" | grep -F -m1 -- "$erwartet")"
                getroffen=$((getroffen + 1))
            fi
        else
            echo "[$id] VERFEHLT (Exit $rc) – erwartet war: $erwartet"
            verfehlt=$((verfehlt + 1))
        fi
    fi
    cp "$SICH/orig" "$datei"
    if [ "$(shasum "$datei" | cut -c1-40)" != "$soll" ]; then
        echo "[$id] RÜCKNAHME NICHT ANGEKOMMEN – Abbruch"; exit 3
    fi
}

A=src/WebUntisAuth.php
mutiere M1 "$A" 'if ($e->getCode() === -8998) {' 'if (true) {' \
    'FEHLER  getCurrentSchoolyear: ein anderer WebUntis-Fehler (-8520) wird NICHT zu []' \
    'getCurrentSchoolyear: -8998 (kein Schuljahr aktiv) ergibt []'
mutiere M2 "$A" '$e->getCode() === -8998' '$e->getCode() === -99999' \
    'FEHLER  getCurrentSchoolyear: -8998 (kein Schuljahr aktiv) ergibt []' \
    'wird NICHT zu []'
mutiere M3 "$A" "public function getRooms(): array    { return \$this->rpc('getRooms'); }" \
    "public function getRooms(): array    { return \$this->rpc('getRooms'); }
    public function getStudents(): array { return \$this->rpc('getStudents'); }" \
    'FEHLER  keine Schülerabfrage (getStudents) im Code des Moduls'
mutiere M4 "$A" "public function getRooms(): array    { return \$this->rpc('getRooms'); }" \
    "public function getRooms(): array    { return \$this->rpc('getRooms'); }
    private function schueler(): array { return \$this->rpc('getStudents'); }" \
    'FEHLER  keine Schülerabfrage (getStudents) im Code des Moduls' \
    'WebUntisAuth führt keine getStudents()'
mutiere M5 "$A" "'getKlassen',
            \$schoolyearId === null ? [] : ['schoolyearId' => \$schoolyearId]" \
    "'getKlassen', []" \
    'FEHLER  getKlassen(7) schickt schoolyearId 7'
mutiere M6 "$A" "rpc('getSchoolYears')" "rpc('getSchoolyears')" \
    'FEHLER  getSchoolyears ruft die JSON-RPC-Methode getSchoolYears'

E=src/extractors.php
mutiere M7 "$E" 'function rest_konto_aus_appdata(' 'function rest_konto_aus_appdata_x(' \
    'extractors_lehrkraefte_test.php'
mutiere M8 "$E" '$mitKlausuren = true' '$mitKlausuren = false' \
    'extractors_klausuren_test.php'

S=tests/alle.sh
mutiere M9 "$S" 'dateien=("$VERZ"/*.php)' 'dateien=("$VERZ"/*_test.php)' \
    'FEHLER  im echten Verzeichnis'
mutiere M10 "$S" '    echo "ROT: $rot von ${#dateien[@]} Dateien"
    exit 1' '    echo "ROT: $rot von ${#dateien[@]} Dateien"
    exit 0' \
    'FEHLER  eine rote Datei macht den Lauf rot'
mutiere M11 "$S" 'elif [ "$ok" -eq 0 ]; then' 'elif false; then' \
    'FEHLER  eine Datei ohne Prüfung macht den Lauf rot'

W=src/WebUntisRest.php
mutiere M12 "$W" '     * Der Scope sagt NICHTS' '     * "mg:r" bedeutet nur LESEN von Mitteilungen.
     * Der Scope sagt NICHTS' \
    'FEHLER  WebUntisRest: die widerlegte Scope-Deutung steht nicht mehr da'
mutiere M13 "$W" '     * GET /WebUntis/api/rest/view/v1/messages/permissions.' '     * die Oberfläche.' \
    'FEHLER  WebUntisRest: der Kommentar nennt die maßgebliche Quelle'

mutiere M14 docs/README_ergaenzung.md '> **ÜBERHOLT – der Versandweg unten ist widerlegt' \
    '> **Hinweis – der Versandweg unten ist widerlegt' \
    'FEHLER  Ergänzungsdoku: das JSON-Beispiel an /v1/messages steht noch da'
mutiere M15 README.md '| 24.07.2026 | `postMultipart(' '| Juli 2026 | `postMultipart(' \
    'FEHLER  README: Abschnitt „Mitteilungen versenden" nennt den gemessenen Weg mit Datum'

# M16: eine Ergänzungsdatei kehrt nach src/ zurück (Datei hinzufügen,
# nicht ersetzen - deshalb ohne mutiere()).
nr=$((nr + 1))
git show HEAD:src/extractors_ergaenzung_v1.6.0.php > src/extractors_ergaenzung_v1.6.0.php 2>/dev/null \
  || git show 7f4df48:src/extractors_ergaenzung_v1.6.0.php > src/extractors_ergaenzung_v1.6.0.php
if [ -s src/extractors_ergaenzung_v1.6.0.php ]; then
    aus="$(php tests/run.php 2>&1)"
    if printf '%s\n' "$aus" | grep -F -q 'FEHLER  kein Funktionsname zweimal in src/'; then
        echo "[M16] getroffen: $(printf '%s\n' "$aus" | grep -F -m1 'FEHLER  kein Funktionsname')"
        getroffen=$((getroffen + 1))
    else
        echo "[M16] VERFEHLT"; verfehlt=$((verfehlt + 1))
    fi
else
    echo "[M16] EINGRIFF NICHT ANGEKOMMEN"; kaputt=$((kaputt + 1))
fi
rm -f src/extractors_ergaenzung_v1.6.0.php
[ -e src/extractors_ergaenzung_v1.6.0.php ] && { echo "[M16] RÜCKNAHME NICHT ANGEKOMMEN"; exit 3; }

# Kein Fehlalarm am Kommentar: Die echte src/WebUntisAuth.php nennt
# getStudents() in einem Kommentar - die Prüfung muss dort schweigen.
kom="$(grep -c 'getStudents' "$A")"
echo "Kommentarstellen mit getStudents in $A: $kom (müssen > 0 sein, Prüfung bleibt grün)"

NACHHER="$(stand)"
rm -rf "$SICH"
echo "Sollstand nach der Serie: $NACHHER"
echo "$nr Mutationen: $getroffen getroffen, $verfehlt verfehlt, $kaputt Eingriffe nicht angekommen"
[ "$VORHER" = "$NACHHER" ] || { echo "STAND WEICHT AB"; exit 3; }
[ "$verfehlt" -eq 0 ] && [ "$kaputt" -eq 0 ] && [ "$kom" -gt 0 ]
