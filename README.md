# webuntis-client-php

**Version 1.7.0** · GPL-3.0-or-later

Framework-freier PHP-Client (PHP 8.1+, nur cURL) für WebUntis:

- **`WebUntisAuth`** – offizielle **JSON-RPC-API** (`/WebUntis/jsonrpc.do`):
  authenticate, getTeachers, getSubjects, getRooms, getTimetable, logout.
  Inklusive der bekannten Fallstricke (JSESSIONID-Weitergabe, personType 16
  mit personId = -1).
- **`WebUntisRest`** – **interne REST-API** (⚠️ undokumentiert): JWT via
  `/api/token/new`, Bearer-Aufrufe auf `/api/rest/view/v1/...` und den
  Legacy-Endpunkt `/api/public/timetable/weekly/data`. `get()`, `post()`,
  `postMultipart()`; eigene Kopfzeilen für alle drei über
  `setzeKopfzeile($name, $wert)` (z. B. für
  `X-Webuntis-Api-School-Year-Id`, siehe Tabelle unten).
- **`src/extractors.php`** – Auswertungsfunktionen, u. a. „welche Lehrkraft
  unterrichtet welches Fach" aus Stundenplan-Antworten.

Entstanden für den Fachkonferenzplaner des Friedrich-Rückert-Gymnasiums
Düsseldorf; das REST-Wissen stammt aus systematischer Sondierung einer
echten Instanz (WebUntis-Stand: Juli 2026).

## Einbindung

Ohne Composer (Stil „einfach reinkopieren"):

```php
require 'src/WebUntisAuth.php';
require 'src/WebUntisRest.php';
require 'src/extractors.php';
```

Mit Composer: `composer require hornse/webuntis-client-php` (classmap).

## Schnellstart: Lehrer-Fach-Zuordnung ermitteln

```php
$wu = new WebUntisAuth('https://SCHULE.webuntis.com', 'SCHULE', 'MeinClient');
$wu->authenticate($benutzer, $passwort);
$lehrer  = $wu->getTeachers();
$faecher = $wu->getSubjects();

// Weg A (offiziell, robust): Stundenplan je Fach
foreach ($faecher as $f) {
    foreach ($wu->getTimetable(3, (int)$f['id'], '20260504', '20260515') as $p) {
        if (($p['lstype'] ?? 'ls') !== 'ls') continue;
        foreach (($p['te'] ?? []) as $te) {
            $paare[$te['id'] . '|' . $f['id']] = true;
        }
    }
}

// Weg B (intern, moderner Endpunkt): ein Aufruf je Lehrkraft für den
// GESAMTEN Zeitraum – Elemente sind selbstbeschreibend (current.type)
$rest = new WebUntisRest('https://SCHULE.webuntis.com', 'SCHULE');
$rest->mitSessionCookie($wu->sessionCookie());
$rest->tokenHolen();
$rest->tenantErmitteln();
foreach ($lehrer as $l) {
    $r = $rest->get('/WebUntis/api/rest/view/v1/timetable/entries', [
        'start' => '2026-05-04', 'end' => '2026-05-15',
        'resourceType' => 'TEACHER', 'resources' => (int)$l['id']]);
    $ex = rest_unterricht_aus_entries($r['json']);
    // $ex['fachKuerzel'] = Fächer dieser Lehrkraft (implizit),
    // $ex['paareExplizit'] = zusätzliche "Lehrer|Fach" aus Kopplungen
}
$wu->logout();
```

## Gesichertes Wissen zur internen REST-API

Aus der Sondierung gegen eine produktive Instanz (frg-dusseldorf, 07/2026):

| Endpunkt | Befund |
|---|---|
| `GET /WebUntis/api/token/new` | liefert JWT als Klartext (Session-Cookie + `schoolname=_base64(schule)` nötig) |
| `GET /api/rest/view/v1/app/data` | 200; enthält `tenant`, `user`, `permissions`, `currentSchoolYear` |
| `GET /api/rest/view/v1/timetable/entries` | **`format`-Parameter WEGLASSEN!** Mit unbekannter Format-ID → 404 „Timetable format not found". Ohne Parameter nutzt die Instanz ihr Standardformat. |
| dito, Antwortstruktur | `{format, days, errors}`; Einträge haben `position1..7`, jedes Element trägt **`current.type`** (`CLASS`/`SUBJECT`/`ROOM`/`TEACHER`) – Positionen NIE fest interpretieren, immer den Typ lesen. `type` des Eintrags z. B. `NORMAL_TEACHING_PERIOD`; Nicht-Unterricht (Aufsichten, Konferenzen, Events) darüber filtern. |
| dito, Batch | `resources=1,2` wird akzeptiert (200), aber die Einträge tragen **keine Zuordnung zur angefragten Ressource** → für Ableitungen je Lehrkraft einzeln abfragen. |
| `GET /api/public/timetable/weekly/data` | Legacy, stabil: `?elementType=2&elementId=<id>&date=YYYY-MM-DD&formatId=1`, eine Woche je Aufruf. Perioden unter `data.result.data.elementPeriods` mit `elements[{type,id,orgId}]` (type 2 = Lehrer, 3 = Fach); bei Vertretung steht die reguläre Lehrkraft in `orgId`. |
| Schuljahresabhängige Pfade (z. B. `/v1/students`) | Die Weboberfläche schickt bei jedem Stundenplanaufruf `X-Webuntis-Api-School-Year-Id` mit. Zwischen zwei Schuljahren ist keines aktiv (`currentSchoolYear: null`); ohne die Kopfzeile bricht die Auswertung solcher Pfade dann ab. Setzbar über `setzeKopfzeile()`. Belegt am 18.08.2026. |

## Tests

```bash
php tests/run.php               # offline, keine Instanz nötig
php tests/webuntis_rest_test.php  # offline, prüft setzeKopfzeile()
```

## Lizenz

GPL v3 – siehe LICENSE.
