<?php
// Offline-Tests für hornse/webuntis-client-php (keine Instanz nötig)
declare(strict_types=1);

require __DIR__ . '/../src/extractors.php';

$fehler = 0;
function pruefe(bool $ok, string $was): void {
    global $fehler;
    echo ($ok ? 'OK  ' : 'FEHLER  ') . $was . "\n";
    if (!$ok) $fehler++;
}

// ------------------------------------------------------------
// 1. entries: echter Eintrag der Instanz frg-dusseldorf (07/2026)
//    + Vertretung + Event + Aufsicht + Kopplung
// ------------------------------------------------------------
$el = fn(string $typ, string $sn, string $status = 'REGULAR') =>
    ['current' => ['type' => $typ, 'status' => $status, 'shortName' => $sn,
                   'longName' => $sn, 'displayName' => $sn], 'removed' => null];

$entries = ['format' => 3, 'errors' => [], 'days' => [[
    'date' => '2026-05-04',
    'gridEntries' => [
        // regulärer Unterricht (Original-Fixture)
        ['ids' => [2618518], 'type' => 'NORMAL_TEACHING_PERIOD', 'status' => 'REGULAR',
         'position1' => [$el('CLASS', 'Q1')], 'position2' => [$el('SUBJECT', 'S7 G1')],
         'position3' => [$el('ROOM', 'BG 1.012')], 'position4' => null],
        // Kopplung: zweite Lehrkraft explizit im Eintrag
        ['type' => 'NORMAL_TEACHING_PERIOD', 'status' => 'REGULAR',
         'position1' => [$el('CLASS', '08B')], 'position2' => [$el('SUBJECT', 'SP')],
         'position3' => [$el('TEACHER', 'Gol')]],
        // Ausfall: zählt trotzdem als Zuordnung
        ['type' => 'NORMAL_TEACHING_PERIOD', 'status' => 'CANCELLED',
         'position1' => [$el('CLASS', '07E')], 'position2' => [$el('SUBJECT', 'WP')]],
        // Vertretung: NICHT werten (Vertreter unterrichtet das Fach nicht regulär)
        ['type' => 'NORMAL_TEACHING_PERIOD', 'status' => 'SUBSTITUTION',
         'position1' => [$el('CLASS', '05A')], 'position2' => [$el('SUBJECT', 'M')]],
        // Aufsicht/Event: NICHT werten
        ['type' => 'BREAK_SUPERVISION', 'status' => 'REGULAR',
         'position1' => [$el('ROOM', 'Cafeteria')], 'position2' => [$el('TEACHER', 'Abe')]],
        ['type' => 'EVENT', 'status' => 'REGULAR',
         'position1' => [$el('CLASS', '05A')], 'position2' => [$el('SUBJECT', 'Wandertag')]],
    ],
]]];

$ex = rest_unterricht_aus_entries($entries);
$faecher = array_keys($ex['fachKuerzel']); sort($faecher);
pruefe($faecher === ['S7 G1', 'SP', 'WP'],
    'entries: Fächer implizit = S7 G1, SP, WP (war: ' . implode(',', $faecher) . ')');
pruefe($ex['eintraege'] === 3, 'entries: 3 gewertete Einträge (war ' . $ex['eintraege'] . ')');
pruefe(array_keys($ex['paareExplizit']) === ['Gol|SP'],
    'entries: Kopplung Gol|SP erkannt, Aufsicht Abe ignoriert');
pruefe(!isset($ex['fachKuerzel']['M']) && !isset($ex['fachKuerzel']['Wandertag']),
    'entries: Vertretung und Event nicht gewertet');

// ------------------------------------------------------------
// 2. rest_erster_eintrag
// ------------------------------------------------------------
$e1 = rest_erster_eintrag($entries);
pruefe($e1 !== null && ($e1['ids'][0] ?? 0) === 2618518, 'rest_erster_eintrag findet ersten Eintrag');
pruefe(rest_erster_eintrag(['days' => []]) === null, 'rest_erster_eintrag: null bei leerer Antwort');

// ------------------------------------------------------------
// 3. weekly/data: Standard, Vertretung (orgId), Periode ohne Fach
// ------------------------------------------------------------
$weekly = ['data' => ['result' => ['data' => [
    'elementIds' => [1744],
    'elementPeriods' => ['1744' => [
        ['date' => 20260504, 'cellState' => 'STANDARD',
         'elements' => [['type' => 1, 'id' => 55, 'orgId' => 0],
                        ['type' => 2, 'id' => 1744, 'orgId' => 0],
                        ['type' => 3, 'id' => 3, 'orgId' => 0]]],
        ['date' => 20260505, 'cellState' => 'SUBSTITUTION',
         'elements' => [['type' => 2, 'id' => 999, 'orgId' => 1744],
                        ['type' => 3, 'id' => 7, 'orgId' => 0]]],
        ['date' => 20260506, 'elements' => [['type' => 2, 'id' => 1744, 'orgId' => 0]]],
    ]],
    'elements' => [['type' => 2, 'id' => 1744, 'name' => 'ho'],
                   ['type' => 3, 'id' => 3, 'name' => 'M'],
                   ['type' => 3, 'id' => 7, 'name' => 'IF']],
]]]];

$w = rest_paare_aus_weekly($weekly);
$paare = array_keys($w['paare']); sort($paare);
pruefe($paare === ['1744|3', '1744|7'],
    'weekly: Paare 1744|3 und 1744|7 (Vertretung -> orgId)');
pruefe($w['perioden'] === 2, 'weekly: Periode ohne Fach ignoriert');
pruefe(($w['namen'][2][1744] ?? '') === 'ho' && ($w['namen'][3][3] ?? '') === 'M',
    'weekly: Namensauflösung');

// ------------------------------------------------------------
// 4. Optionale Methoden: is_callable(), nicht method_exists()
//    method_exists() meldet auch PRIVATE Methoden als vorhanden.
//    Eine Wache damit laesst den Aufruf durch, er wirft einen Error,
//    ein catch (Throwable) faengt ihn - und die Anwendung meldet
//    "WebUntis nicht erreichbar", ohne je verbunden zu haben.
//    (Befund A9, hornse/koordination, 24.09.2026)
// ------------------------------------------------------------
require_once __DIR__ . '/../src/WebUntisRest.php';

// 4a. Kein Aufruf von method_exists im Code des Moduls. Der
//     Tokenizer trennt Kommentar und Zeichenkette vom Code - sonst
//     schluege die Pruefung auf die Begruendung an.
$codeDateien = array_merge(glob(__DIR__ . '/../src/*.php') ?: [],
                           glob(__DIR__ . '/../beispiele/*.php') ?: []);
pruefe(count($codeDateien) > 0, 'Code des Moduls gefunden (' . count($codeDateien) . ' Dateien)');
$mitMethodExists = [];
foreach ($codeDateien as $datei) {
    foreach (token_get_all((string)file_get_contents($datei)) as $t) {
        if (is_array($t) && $t[0] === T_STRING && strtolower($t[1]) === 'method_exists') {
            $mitMethodExists[] = basename($datei) . ':' . $t[2];
        }
    }
}
pruefe($mitMethodExists === [],
    'kein method_exists im Code des Moduls' . ($mitMethodExists ? ' – gefunden: ' . implode(', ', $mitMethodExists) : ''));

// 4b. Die Tatsache, auf der die Empfehlung beruht - am echten
//     rohGet(). Wird rohGet() oeffentlich, stimmt das Beispiel in
//     der README nicht mehr, und das soll hier auffallen.
$restProbe = new WebUntisRest('https://x.invalid', 'y');
pruefe(method_exists($restProbe, 'rohGet') === true,
    'method_exists meldet die private rohGet() als vorhanden');
pruefe(is_callable([$restProbe, 'rohGet']) === false,
    'is_callable meldet die private rohGet() als nicht aufrufbar');
pruefe(is_callable([$restProbe, 'tokenHolen']) === true,
    'GEGENPROBE: bei der öffentlichen tokenHolen() sagt is_callable ja');

// 4c. Die Empfehlung in der README. Gesucht wird im Abschnitt, und
//     fuer das Muster in den Codebloecken - die Prosa darf
//     method_exists nennen, sie erklaert ja, warum nicht.
$readme = (string)file_get_contents(__DIR__ . '/../README.md');
$abschnitt = '';
if (preg_match('/^### Optionale Methoden\b.*?(?=^#)/ms', $readme, $m)) {
    $abschnitt = $m[0];
}
pruefe($abschnitt !== '', 'README: Abschnitt „Optionale Methoden" vorhanden');
preg_match_all('/^```[a-z]*\n(.*?)^```/ms', $abschnitt, $bl);
$abschnittCode = implode("\n", $bl[1]);
pruefe(str_contains($abschnittCode, "is_callable([\$rest, 'rohGet'])"),
    'README: Codebeispiel prüft mit is_callable am Beispiel rohGet()');
preg_match_all('/^```[a-z]*\n(.*?)^```/ms', $readme, $alle);
pruefe(count($alle[1]) > 0 && !str_contains(implode("\n", $alle[1]), 'method_exists'),
    'README: kein method_exists in einem Codeblock (' . count($alle[1]) . ' Blöcke)');
pruefe(str_contains($abschnitt, '`Error`') && str_contains($abschnitt, 'catch (Throwable')
       && str_contains($abschnitt, 'nicht erreichbar'),
    'README: die Folge ist genannt (Error, catch (Throwable), „nicht erreichbar")');

// ------------------------------------------------------------
echo "\n" . ($fehler === 0 ? 'ALLE TESTS BESTANDEN' : "$fehler TEST(S) FEHLGESCHLAGEN") . "\n";
exit($fehler === 0 ? 0 : 1);
