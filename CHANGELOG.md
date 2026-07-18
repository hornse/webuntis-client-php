# Changelog – webuntis-client-php

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
