# README-Ergänzung für hornse/webuntis-client-php

Diese Abschnitte in die bestehende `README.md` einfügen.

---

## In den Abschnitt „Extraktoren" ergänzen

### `rest_lehrkraefte_aus_entries($json, bool $mitKlausuren = true): array` *(ab v1.2.0, Parameter ab v1.6.0)*

Gegenstück zu `rest_unterricht_aus_entries()`. Beide werten dieselbe
Antwort von `/timetable/entries` aus, aber aus entgegengesetzter Richtung:

| Funktion | Abfrage je | gesucht wird |
|---|---|---|
| `rest_unterricht_aus_entries()` | Lehrkraft (`resourceType=TEACHER`) | Fächer (Lehrkraft ist implizit) |
| `rest_lehrkraefte_aus_entries()` | Schüler (`resourceType=STUDENT`) | Lehrkräfte (stehen explizit im Eintrag) |

Typische Frage: *Welche Lehrkräfte unterrichten dieses Kind?* – etwa für
Elternsprechtag-Buchungen.

```php
$r = $rest->get('/WebUntis/api/rest/view/v1/timetable/entries', [
    'start' => '2026-06-15', 'end' => '2026-07-10',
    'resourceType' => 'STUDENT', 'resources' => 13914,
    // KEIN format-Parameter!
]);
$ex = rest_lehrkraefte_aus_entries($r['json']);

// $ex['eintraege']   -> 42   (Zahl gewerteter Unterrichtsstunden)
// $ex['lehrkraefte'] -> [
//   'Gr' => ['name' => 'Greitemann', 'stunden' => 8, 'klausuren' => 0,
//            'faecher' => ['M' => 4, 'WP' => 4]],
//   'Kl' => ['name' => 'Klein',      'stunden' => 3, 'klausuren' => 1,
//            'faecher' => ['E' => 3]],
// ]
```

Das Ergebnis ist **nach Stundenzahl absteigend sortiert** – Hauptfach-
lehrkräfte stehen also oben, was sich gut für Auswahllisten eignet.

**Klausuren** (ab v1.6.0): Mit `$mitKlausuren = true` (Standard) werden
zusätzlich `EXAM`-Einträge mit Status `REGULAR` gewertet – in Unter- und
Mittelstufe beaufsichtigen Fachlehrkräfte ihre eigenen Arbeiten, und ohne
diese Einträge fällt eine Lehrkraft heraus, deren regulärer Unterricht im
Abfragezeitraum vertreten wurde. Klausurstunden zählen getrennt in
`klausuren` und **nicht** in `stunden`, damit die Sortierung nach
regulärem Unterricht erhalten bleibt. Wo Aufsichten fachfremd verteilt
werden, mit `false` abschalten.

**Filterregeln** (identisch zu `rest_unterricht_aus_entries()`):

* nur Einträge, deren `type` `TEACHING` enthält – schließt
  `BREAK_SUPERVISION` (Pausenaufsicht), `EVENT` (Wandertag) und `EXAM` aus
* nur Status `REGULAR` oder `CANCELLED`. **Ein Ausfall zählt mit**, denn er
  ändert nichts daran, wer das Fach regulär unterrichtet – sonst würde eine
  Lehrkraft fehlen, deren Stunde im Abfragezeitraum zufällig entfiel
* `SUBSTITUTION` und `CHANGED` werden übersprungen, damit Vertretungen
  keine falsche Zuordnung erzeugen
* es wird ausschließlich `current` gelesen, `removed` ignoriert
* Positionsnummern werden nicht interpretiert (sie sind formatabhängig);
  jedes Element beschreibt sich über `current.type` selbst

**Zeiträume:** `start`/`end` sind frei wählbar. Für eine verlässliche
Zuordnung empfiehlt sich ein Zeitraum von etwa vier Wochen – so werden auch
14-tägige Kurse und Epochenunterricht erfasst. Der Zeitraum sollte in der
Unterrichtszeit liegen; in den Ferien liefert die API `status: "NO_DATA"`
und leere `gridEntries`.

**Mehrere Schüler:** je Schüler einzeln abfragen. Eine Komma-Liste in
`resources` wird zwar akzeptiert, die Einträge tragen aber keine Zuordnung
zur angefragten Ressource.

### `rest_konto_aus_appdata($json): array` *(ab v1.2.0)*

Liest Konto-Eckdaten und verknüpfte Kinder aus
`GET /WebUntis/api/rest/view/v1/app/data`.

```php
$app = $rest->get('/WebUntis/api/rest/view/v1/app/data');
$k = rest_konto_aus_appdata($app['json']);

// [
//   'userId'   => 5984,                  // Login-Konto
//   'personId' => 476,                   // = personId aus authenticate()
//   'rollen'   => ['LEGAL_GUARDIAN'],
//   'kinder'   => [['id' => 13914, 'name' => 'Muster Paul'], …],
// ]
```

**Drei IDs, die nicht verwechselt werden dürfen:**

| Feld | Bedeutung | Verwendung |
|---|---|---|
| `user.id` | **User-ID** des Login-Kontos | Adressat für Mitteilungen |
| `user.person.id` | **Person-ID** (= `personId` aus `authenticate`) | Stammdaten-Bezug |
| `user.students[].id` | Schüler-Person-IDs der Kinder | Stundenplan-Abfrage |

`user.person` ist die eigene Person des angemeldeten Kontos und **niemals
ein Kind** – wer das verwechselt, fragt den Stundenplan der Eltern ab und
bekommt `NOT_FOUND`. Die Funktion liefert deshalb nur `user.students`.

Bei Lehrkräften und Admins ist `students` leer; das eignet sich als
zusätzliches Merkmal zur Rollenerkennung. Der Admin-Platzhalter `-1` wird
verworfen.

---

## In den Abschnitt „WebUntisAuth" ergänzen

### `getKlassen(): array` und `getStudents(): array` *(ab v1.4.0)*

Stammdaten-Abrufe über JSON-RPC, analog zu `getTeachers()`:

```php
$klassen  = $wu->getKlassen();    // [['id'=>42,'name'=>'6b', …], …]
$schueler = $wu->getStudents();   // [['id'=>13914,'name'=>…, …], …]
```

> **Datenschutz:** `getStudents()` liefert die gesamte Schülerschaft mit
> Klarnamen. Nur die benötigten Felder weiterverarbeiten (meist `id` und
> `klasseId`), Namen nicht speichern, wenn IDs genügen, und die Antwort
> nicht in Logs oder Diagnoseberichte schreiben.

---

## In den Abschnitt „WebUntisRest" ergänzen

### `post(string $pfad, array $daten): array` *(ab v1.3.0)*

Schreibender Zugriff mit JSON-Body. Rückgabe wie bei `get()`:
`['status', 'contentType', 'text', 'json']`.

```php
$antwort = $rest->post('/WebUntis/api/rest/view/v1/messages', [
    'subject'    => 'Terminbestätigung',
    'content'    => 'Ihr Termin am …',
    'recipients' => [5984],          // USER-ID, nicht personId!
]);
if ($antwort['status'] >= 200 && $antwort['status'] < 300) { /* … */ }
```

> **Achtung:** Schreibender Zugriff auf eine undokumentierte Schnittstelle.
> Die erwartete Feldstruktur ist nicht öffentlich spezifiziert und kann sich
> mit WebUntis-Updates ändern. Aufrufer sollten den Status auswerten, mit
> Fehlschlägen rechnen und einen Fallback vorsehen. Ein Vorgehen mit
> mehreren Kandidaten-Strukturen zeigt das Projekt `sprechtag`
> (`backend/api/mitteilungen.php`).

### `setzeTimeout(int $sekunden): void` *(ab v1.3.0)*

Setzt das Timeout je Aufruf (Standard 25 s). Bei vielen Aufrufen in Folge –
etwa Diagnoseläufen – sollte der Wert niedriger liegen, damit der
Gesamtdurchlauf unter dem Proxy-Limit des Webservers bleibt (Uberspace
kappt bei ca. 60 s).

---

## Neuer Abschnitt: „Bekannte Endpunkte"

Befunde einer Sondierung gegen eine WebUntis-Instanz (07/2026). Die interne
REST-API ist undokumentiert; diese Angaben sind empirisch und ohne Gewähr.

| Endpunkt | Methode | Befund |
|---|---|---|
| `/WebUntis/api/token/new` | GET | JWT als Klartext, für alle Kontotypen |
| `/api/rest/view/v1/app/data` | GET | Konto, Rollen, Kinder, Zeitraster, Schuljahr |
| `/api/rest/view/v1/timetable/entries` | GET | Stundenplan; `resourceType` = `STUDENT`/`CLASS`/`TEACHER` |
| `/api/rest/view/v1/messages` | GET | Posteingang |
| `/api/rest/view/v1/messages/status` | GET | Anzahl ungelesener Nachrichten |
| `/api/rest/view/v1/messages/recipients` | GET | erwartet Parameter vom Typ `Long` (User-ID) |

**personType-Werte** (aus `authenticate`):

| Wert | Rolle | Besonderheit |
|---|---|---|
| 2 | Lehrkraft | Kürzel über `getTeachers()` |
| 5 | Schüler:in | |
| 12 | Erziehungsberechtigte:r | Rolle `LEGAL_GUARDIAN`, Kinder in `user.students` |
| 16 | WebUntis-Admin | `personId = -1`, **kein** Eintrag in `getTeachers()` |
