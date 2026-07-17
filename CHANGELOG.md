# Changelog – webuntis-client-php

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
