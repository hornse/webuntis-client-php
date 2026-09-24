# Changelog – webuntis-client-php

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
