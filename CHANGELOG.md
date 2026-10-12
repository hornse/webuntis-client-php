# Changelog – webuntis-client-php

## [2.0.0] – 12.10.2026

**Der erste Tag dieses Repos.** Grundlage: Auftrag
`AUFTRAG-webuntis-client-zusammenfuehren.md` und
`docs/BEFUND-2026-10-12-webuntis-client-bestand.md` (beide 12.10.2026).

### Was die Nummern davor bedeutet haben

Keine von ihnen war getaggt. Sie stehen in Commit-Titeln, in
Kopfvermerken vendorter Kopien und in Dateinamen – **als Behauptungen,
nicht als Belege**:

| Nummer | wo sie steht | was der Code an dieser Stelle tatsächlich enthielt |
|---|---|---|
| 1.0.0 | Commit 9018d5c (17.07.), Überschrift unten | der Grundstock: `WebUntisAuth`, `WebUntisRest`, `extractors.php` mit drei Funktionen |
| 1.1.0 | Commit e28bc00 (18.07.) | Zählwerte in `extractors.php` |
| 1.2.0 | Datei `extractors_ergaenzung_v1.2.0.php` (im „v1.3.0"-Commit 2e11a4e) | **nur Anleitung** – nicht in `src/extractors.php` |
| 1.3.0 | Commit 2e11a4e (23.07.) | **nur Anleitungen**; `src/WebUntisRest.php` unverändert |
| 1.4.0 | zwei Abschnitte in `docs/CHANGELOG_ergaenzung.md`, Kommentare in Kopien | **nirgends** – kein Commit, keine Datei |
| 1.5.0, 1.6.0 | Commits 2b6838a, 5eb22f4 (24.07.) | **nur Anleitungen** und zwei Testdateien, die seitdem mit einem Fatal brachen |
| 1.7.0 | Commit 6cd8afd (25.08.), Kopfvermerk von `WebUntisRest.php` in vier Projekten | der Rückfluss von `WebUntisRest` aus `sprechtag` – **echt im Code** |

**Ein Kopfvermerk lügt:** `extractors.php` in `lernzeiten` und
`sprechtag` nennt sich „v1.2.0", ist inhaltlich die Ergänzung **1.6.0**
(zweiter Parameter `$mitKlausuren`) und hat **keine einzige Funktion**
mit der damaligen Moduldatei gemeinsam.

**Warum 2 und nicht 1:** „1.0.0" bezeichnet schon den Stand vom 17.07.
(Commit-Titel und Überschrift unten), und eine Nummer ist ein Name. Und
der Stand ist gegenüber den Kopien in den Projekten **nicht
rückwärtsverträglich** – siehe „Geändert".

### Zusammengeführt

- `src/extractors.php` führt jetzt auch `rest_lehrkraefte_aus_entries($json,
  bool $mitKlausuren = true)` und `rest_konto_aus_appdata()`, in der
  Fassung **1.6.0**, Code unverändert (Rumpf-Prüfsummen gleich der
  Ergänzung und den Kopien in `lernzeiten`/`sprechtag`). Die beiden
  Ergänzungsdateien sind entfernt; zwei Funktionen gleichen Namens in
  `src/` wären beim gemeinsamen Laden ein Fatal.
- `WebUntisAuth`: `getKlassen(?int $schoolyearId = null)`,
  `getSchoolyears()`, `getCurrentSchoolyear()` aus der Anleitung 1.5.0.
- Die beiden Anleitungen (`*_ergaenzung_*.md`) liegen jetzt unter `docs/`,
  als ÜBERHOLT gekennzeichnet; in `src/` steht nur noch Code.

### Geändert (nicht rückwärtsverträglich gegenüber den Kopien)

- **`getStudents()` steht absichtlich nicht im Modul.** Die Schülerliste
  ist personenbezogen; das Modul wird in jede Anwendung verteilt, auch in
  die, die sie nicht brauchen (REIHENREGELN 9). Die Kopien in
  `lernzeiten`, `signage`, `sprechtag` führen sie.
- **`getCurrentSchoolyear()` fängt nur noch -8998** (und behandelt das
  Ergebnis `null`) als „kein Schuljahr aktiv". Die Anleitung fing jede
  `RuntimeException` – ein Netzfehler sah aus wie die Sommerferien. Jetzt
  wird jeder andere Fehler weitergereicht.

### Berichtigt

- **Kommentar an `jwtDaten()`:** „mg:r bedeutet nur Lesen, ohne
  Schreibrecht scheitert der Versand mit 403" ist widerlegt (lernzeiten
  06.10., sprechtag 07.10.2026, dort E1). Maßgeblich ist
  `/v1/messages/permissions`.
- **Versandweg in der Doku:** gemessen ist multipart (24.07., 06.10.,
  09.10.2026). Die README nennt ihn mit Belegen (Abschnitt „Mitteilungen
  versenden"); der JSON-Weg an `/v1/messages` steht in
  `docs/README_ergaenzung.md` als ÜBERHOLT gekennzeichnet, nicht gelöscht.
- Die zwei Feldbeschreibungen von `getStudents()` und der Verweis auf eine
  „Ergänzung v1.4.0" sind in den Anleitungen gekennzeichnet.

### Prüfungen

**31 → 111**, alle grün (vorher genannte Erwartung: 111).

| Datei | vorher | nachher |
|---|---:|---:|
| `tests/run.php` | 23 | 31 |
| `tests/webuntis_rest_test.php` | 8 | 8 |
| `tests/extractors_lehrkraefte_test.php` | Fatal | 36 |
| `tests/extractors_klausuren_test.php` | Fatal | 14 |
| `tests/webuntis_auth_test.php` (neu, mit Probeserver) | – | 12 |
| `tests/sammelskript_test.php` (neu) | – | 10 |

- **`tests/alle.sh`** startet alle Testdateien, ermittelt sie selbst und
  ist rot bei Exit ≠ 0, bei einer Datei ohne Prüfung und bei einem leeren
  Verzeichnis. Bis hierher startete kein Skript alle, und die README
  nannte nur die grünen.
- 16 Mutationen, alle getroffen, mit Wirkungs- und Rücknahmenachweis:
  `docs/beilagen/2026-10-12-mutationen.sh`.

## Ohne Versionsnummer (24.09.2026)

Das Repo führt keine Tags; die Vergabe einer Nummer ist Teil eines
offenen Vorgangs. Diese Änderung bleibt deshalb ohne Versionsangabe.

- **README, Abschnitt „Optionale Methoden"**: Optionale Methoden einer
  vendorten Kopie mit `is_callable()` prüfen, nicht mit
  `method_exists()` — letzteres meldet auch die private `rohGet()` als
  vorhanden. Folge einer solchen Wache: `Error`, gefangen von
  `catch (Throwable)`, gemeldet als „nicht erreichbar". Anlass: Befund
  A9 in `hornse/koordination`.
- `tests/run.php`: 9 → 18 Prüfungen. Neu: kein `method_exists` im Code
  des Moduls (Tokenizer, Kommentare und Zeichenketten ausgenommen),
  `rohGet()` ist privat, die README-Empfehlung steht im Codeblock und
  nennt die Folge. Sieben Gegenproben gefahren, alle angeschlagen.

## v1.7.0 (August 2026)

- **`WebUntisRest::setzeKopfzeile($name, $wert)`** – eigene Kopfzeile für
  alle künftigen Anfragen (`get`, `post`, `postMultipart`, auch
  `tokenHolen`). Anlass: WebUntis erwartet bei schuljahresabhängigen
  REST-Aufrufen `X-Webuntis-Api-School-Year-Id`; ohne aktives Schuljahr
  (zwischen zwei Schuljahren) sonst nicht zuverlässig auszuwerten. Rein
  additiv, kein bestehender Aufruf ändert sich ohne Angabe.
- Kopfzeilenaufbau in `rohGet()`, `post()`, `postMultipart()` in eine
  gemeinsame private `baueKopfzeilen()` gezogen. Vorher drei fast
  gleiche Blöcke – lässt sich jetzt zudem ohne Netzzugriff testen
  (`tests/webuntis_rest_test.php`).
- `src/WebUntisRest.php` erhält damit auch die Methoden, die seit Juli
  2026 in `sprechtag` in Betrieb sind, aber nie zurückgespielt wurden:
  `setzeTimeout()`, `jwtDaten()`, `jwtScopes()`, `post()`,
  `postMultipart()`, `empfaengerSuchen()`, `listeAufloesen()`.
- **Zur Versionsgeschichte:** Die Commits v1.2.0–v1.5.0 haben nur
  Anleitungsdateien (`*_ergaenzung_*.md/php`, `EINFUEGEN.md`) hinzugefügt,
  nicht den Quellcode. `src/WebUntisRest.php` stand bis zu diesem Sprung
  unverändert auf dem Stand von v1.0.0 (17.07.2026), obwohl die
  Commit-Historie post()/setzeTimeout() für v1.3.0 auswies. Festgehalten,
  damit die Versionsgeschichte nicht mehr behauptet als der Code hält.
  `src/extractors.php` und `src/WebUntisAuth.php` haben denselben
  Rückstand – hier nicht angefasst.

## v1.1.0 (Juli 2026)

- Extraktoren zählen Vorkommen: fachKuerzel/paareExplizit/paare liefern
  Anzahl statt true (rückwärtskompatibel über array_keys) – Grundlage für
  Stunden-Signale wie „Facultas vs. Vertretung"

## v1.0.0 (Juli 2026)

- WebUntisAuth: offizielle JSON-RPC-API (authenticate, getTeachers,
  getSubjects, getRooms, getTimetable, logout; JSESSIONID-Handling,
  personType-16-Fallstrick dokumentiert)
- WebUntisRest: interne REST-API (JWT via /api/token/new, tenant-id,
  generisches GET)
- Extraktoren: rest_unterricht_aus_entries (typbasiert, filtert
  Nicht-Unterricht und Vertretungen), rest_paare_aus_weekly
  (Legacy weekly/data, orgId-Vertretungslogik), rest_erster_eintrag
- Offline-Testsuite, Sondierungswissen im README dokumentiert
