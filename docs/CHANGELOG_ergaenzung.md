# CHANGELOG-Ergänzung für hornse/webuntis-client-php

Diese Einträge oben in die bestehende `CHANGELOG.md` einfügen.

---

## v1.5.0 (Juli 2026)

### Neu
- `WebUntisAuth::getSchoolyears()` – alle Schuljahre mit id, name,
  startDate, endDate.
- `WebUntisAuth::getCurrentSchoolyear()` – aktuelles Schuljahr oder `[]`.
  Fängt den Fehler -8998 ab, der zwischen zwei Schuljahren auftritt.
- `WebUntisAuth::getKlassen()` nimmt jetzt optional eine `schoolyearId`.

### Erkenntnis aus der Praxis
In den Sommerferien ist **kein Schuljahr aktiv**. Aufrufe, die sich implizit
auf das aktuelle Schuljahr beziehen (z. B. `getKlassen()` ohne Parameter),
scheitern dann mit:

```
WebUntis-Fehler -8998: Cannot invoke
"com.grupet.web.basic.Schoolyear.getEndDate()" because "schoolyear" is null
```

Wer ganzjährig zuverlässig arbeiten muss, sollte die Schuljahres-ID
explizit übergeben.

---

## v1.4.0 (Juli 2026)

### Neu
- `WebUntisAuth::getKlassen()` – alle Klassen.
- `WebUntisAuth::getStudents()` – alle Schüler:innen.

### Hinweis
`getStudents()` liefert je Eintrag nur `id`, `key`, `name`, `foreName`,
`longName`, `gender` – **keine Klassen- oder Jahrgangszuordnung** und keine
Gruppenzugehörigkeit. Für Zuordnungen dieser Art führt kein Weg an den
Stundenplänen vorbei.

---

## v1.4.0 (Juli 2026)

### Neu
- `WebUntisAuth::getKlassen()` – Klassenliste des aktuellen Schuljahres.
- `WebUntisAuth::getStudents()` – alle Schüler:innen mit `id`, `key`,
  `name`, `foreName`, `longName` und je nach Instanz weiteren Feldern.

### Datenschutzhinweis
`getStudents()` liefert die gesamte Schülerschaft mit Klarnamen. Aufrufer
sollten nur die benötigten Felder verarbeiten (meist `id` und `klasseId`),
Namen nicht dauerhaft speichern, wenn IDs ausreichen, und die Antwort
nicht in Logs oder Diagnoseberichte schreiben.

---

## v1.3.0 (Juli 2026)

### Neu
- `WebUntisRest::post()` – schreibender Zugriff mit JSON-Body. Gedacht für
  Endpunkte wie den Versand von Mitteilungen. Rückgabe wie bei `get()`
  (`status`, `contentType`, `text`, `json`), Fehler werden als
  `status: 0` mit cURL-Meldung gemeldet statt als Ausnahme.
- `WebUntisRest::setzeTimeout()` – Timeout je Aufruf konfigurierbar
  (Standard weiterhin 25 s). Nötig für Diagnoseläufe mit vielen Aufrufen
  in Folge, die sonst in das Proxy-Limit des Webservers laufen.

### Hinweis
Der POST-Weg der internen REST-API ist **nicht dokumentiert**. Die
erwartete Feldstruktur wurde nicht verifiziert. Aufrufer sollten mehrere
Strukturen probieren, den Status auswerten und einen Fallback vorsehen.

---

## v1.2.0 (Juli 2026)

### Neu
- `rest_lehrkraefte_aus_entries()` – ermittelt aus einer
  `/timetable/entries`-Antwort mit `resourceType=STUDENT` alle
  unterrichtenden Lehrkräfte samt Fächern und Stundenzahl, absteigend nach
  Stundenzahl sortiert. Gegenstück zu `rest_unterricht_aus_entries()`
  (dort wird je Lehrkraft abgefragt, hier je Schüler).
  Nutzt dieselben Filterregeln: nur `TEACHING`-Einträge, nur Status
  `REGULAR`/`CANCELLED`, ausschließlich `current`.
- `rest_konto_aus_appdata()` – liest User-ID, Person-ID, Rollen und
  verknüpfte Kinder aus `app/data`. Unterscheidet sauber zwischen
  `user.id` (Login-Konto), `user.person.id` (Person) und
  `user.students[].id` (Kinder) und verwirft den Admin-Platzhalter `-1`.

### Tests
- `tests/extractors_lehrkraefte_test.php` – 34 Prüfungen, ohne Netz und DB
  lauffähig: Filterregeln (Vertretung, Ausfall, Pausenaufsicht, Event,
  Klausur), Kopplungen, `removed`-Behandlung, Sortierung, leere und
  fehlerhafte Eingaben, Konto-Auswertung für Eltern/Lehrkraft/Admin.

### Erkenntnisse aus der Praxis
- `personType 12` = Erziehungsberechtigte (`LEGAL_GUARDIAN`); die
  verknüpften Kinder stehen in `app/data` unter `user.students[]`.
- `user.person` ist die **eigene** Person des Kontos und darf nicht als
  Kind interpretiert werden – sonst schlägt die Stundenplanabfrage mit
  `NOT_FOUND` fehl.
- Ein Zeitraum von etwa vier Wochen erfasst auch 14-tägige Kurse; in den
  Ferien liefert die API `status: "NO_DATA"` und leere `gridEntries`.
