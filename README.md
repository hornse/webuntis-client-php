# webuntis-client-php

**Version 2.0.0** (Tag `v2.0.0`) · GPL-3.0-or-later

> Die Nummern 1.1.0 bis 1.7.0 aus früheren Commit-Titeln und Kopfvermerken
> waren nie getaggt. `v2.0.0` ist der **erste Tag** und enthält alles, was
> sie bezeichneten – siehe `CHANGELOG.md`. Es ist die 2, weil „1.0.0“
> schon den Stand vom 17.07.2026 bezeichnet, und weil der Stand
> gegenüber den Kopien in den Projekten nicht rückwärtsverträglich ist.

Framework-freier PHP-Client (PHP 8.1+, nur cURL) für WebUntis:

- **`WebUntisAuth`** – offizielle **JSON-RPC-API** (`/WebUntis/jsonrpc.do`):
  authenticate, getTeachers, getSubjects, getRooms, getKlassen,
  getSchoolyears, getCurrentSchoolyear, getTimetable, logout.
  Inklusive der bekannten Fallstricke (JSESSIONID-Weitergabe, personType 16
  mit personId = -1, kein aktives Schuljahr im Sommer).
  **Keine Schülerliste:** `getStudents()` ist personenbezogen und steht
  absichtlich nicht im Modul.
- **`WebUntisRest`** – **interne REST-API** (⚠️ undokumentiert): JWT via
  `/api/token/new`, Bearer-Aufrufe auf `/api/rest/view/v1/...` und den
  Legacy-Endpunkt `/api/public/timetable/weekly/data`. `get()`, `post()`,
  `postMultipart()`; eigene Kopfzeilen für alle drei über
  `setzeKopfzeile($name, $wert)` (z. B. für
  `X-Webuntis-Api-School-Year-Id`, siehe Tabelle unten).
- **`src/extractors.php`** – Auswertungsfunktionen, u. a. „welche Lehrkraft
  unterrichtet welches Fach" aus Stundenplan-Antworten
  (`rest_unterricht_aus_entries`, `rest_erster_eintrag`,
  `rest_paare_aus_weekly`), „welche Lehrkräfte unterrichten dieses Kind"
  (`rest_lehrkraefte_aus_entries($json, bool $mitKlausuren = true)`) und
  die Konto-Eckdaten aus `app/data` (`rest_konto_aus_appdata`). Die beiden
  letzten sind beschrieben in `docs/README_ergaenzung.md` (dort mit
  Vermerken; sachlich gilt der Code).

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

### Was `WebUntisRest` seit dem Rückfluss führt — keine Wache nötig

**Seit dem 25.08.2026** (`6cd8afd`, in der Commit-Meldung als v1.7.0
bezeichnet) stehen diese Methoden im **Code** von
`src/WebUntisRest.php`:

`setzeTimeout()` · `setzeKopfzeile()` · `jwtDaten()` · `jwtScopes()` ·
`post()` · `postMultipart()` · `empfaengerSuchen()` ·
`listeAufloesen()`

**Alle sind `public`.** Wer sie aufruft, braucht keine Wache — eine
Prüfung, deren Antwort feststeht, ist keine.

> **Achtung bei der Datierung.** `git log -S 'function setzeTimeout'`
> nennt **ohne** Pfadfilter zusätzlich v1.3.0 (23.07.2026). Dort stand
> die Methode aber nur in `src/WebUntisRest_ergaenzung_v1.3.0.md` —
> einer **Anleitungsdatei**, nicht in lauffähigem PHP. Der Rückfluss
> in den Code fand erst am 25.08.2026 statt. Wer die Nummer aus dem
> Commit-Titel übernimmt, nennt eine Fassung, in der der Code die
> Methode nicht hatte.

Und weil dieses Repo **keine Tags führt** und die Nummern in den
Commit-Meldungen nicht durchgängig zum Inhalt passen, ist das **Datum**
die tragfähigere Angabe — die Nummer steht daneben, nicht dafür.

### Wirklich optionale Methoden: `is_callable()`, nicht `method_exists()`

Wer eine vendorte Kopie auf eine Methode prüft, die nicht jede Fassung
führt, fragt mit `is_callable()`:

```php
// rohGet() ist private. Von außen gefragt:
is_callable([$rest, 'rohGet']);   // false – nicht aufrufbar
```

**`method_exists()` meldet auch private Methoden als vorhanden** —
`method_exists($rest, 'rohGet')` liefert `true`. Eine Prüfung auf
**Vorhandensein** ist keine auf **Benutzbarkeit**.

Die Folge schickt die Fehlersuche in die falsche Richtung: Die Wache
lässt den Aufruf durch, er wirft einen `Error` (keine `Exception`), ein
`catch (Throwable $e)` fängt ihn — und die Anwendung meldet „WebUntis
nicht erreichbar". Eine Verbindung wurde nie versucht. In einem Projekt
hat das zwei Tage Suche an der falschen Stelle gekostet.

Bei einer öffentlichen Methode liefern beide dasselbe; gerade deshalb
fällt der Unterschied erst bei einer privaten auf. **`setzeTimeout()`
ist dafür kein Beispiel mehr** — sie ist seit dem Rückfluss Bestand
(siehe oben), und eine Wache davor beantwortet eine Frage, deren
Antwort feststeht.
`tests/run.php` hält fest, dass `rohGet()` privat ist und im Code des
Moduls kein `method_exists` steht.

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

### Mitteilungen versenden

**Gemessen ist multipart**, nicht ein JSON-Body: Die Oberfläche schickt
das JSON als Datei-Teil `request` einer Multipart-Nachricht.
`postMultipart()` baut genau das.

| Datum | Weg | Beleg |
|---|---|---|
| 24.07.2026 | `postMultipart('/WebUntis/api/rest/view/v2/messages/users', [… 'recipientUserIds' => [<user.id>] …])` – an Konten | Mitschnitt der Oberfläche, `sprechtag` |
| 06.10.2026 | `postMultipart('/WebUntis/api/rest/view/v2/messages', …)` | Messung `lernzeiten` |
| 09.10.2026 | `/v2/messages` mit `recipientOption: PARENTS` → 200, im Elternpostfach angekommen; derselbe Körper an `/v2/messages/users` → 500 | `koordination` M48 |

Adressiert wird an `/v2/messages/users` die **`user.id`** (aus `app/data`,
siehe `rest_konto_aus_appdata()`), nicht die `personId`. `PARENTS` geht
nur über eine Lehrkraft-Sitzung; das Admin-Konto bekommt 403 (M48).

**Der früher dokumentierte Weg – `post()` mit JSON-Body an
`/v1/messages` – ist nirgends als gesendet belegt.** Er steht als
ÜBERHOLT gekennzeichnet in `docs/README_ergaenzung.md`.

**Ob ein Konto senden darf, sagt nicht der JWT-Scope** (`mg:r` trägt
auch die Oberfläche, die sendet), sondern
`GET /WebUntis/api/rest/view/v1/messages/permissions`.

Keine dieser Messungen trägt die WebUntis-Version.

## Tests

```bash
bash tests/alle.sh    # ALLE Testdateien, jede einzeln; offline
```

Das Skript ermittelt die Testdateien selbst (jede `tests/*.php`) – eine
neue läuft mit, ohne dass hier etwas ergänzt wird. Rot ist ein
Exit-Code ungleich 0, eine Datei ohne erkennbare Prüfung und ein leeres
Verzeichnis. Bis v2.0.0 nannte diese Stelle nur die grünen Dateien, und
zwei rote blieben sechs Wochen unbemerkt.

`tests/webuntis_auth_test.php` startet selbst einen lokalen PHP-Server
(`php -S`) und prüft `WebUntisAuth` über das echte `rpc()`.

## Lizenz

GPL v3 – siehe LICENSE.
