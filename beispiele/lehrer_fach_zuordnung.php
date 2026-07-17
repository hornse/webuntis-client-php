<?php
// Beispiel: Lehrer-Fach-Zuordnung über die interne REST-API ermitteln
// Aufruf: php beispiele/lehrer_fach_zuordnung.php BENUTZER PASSWORT
require __DIR__ . '/../src/WebUntisAuth.php';
require __DIR__ . '/../src/WebUntisRest.php';
require __DIR__ . '/../src/extractors.php';

[$basis, $schule] = ['https://frg-dusseldorf.webuntis.com', 'frg-dusseldorf'];
$wu = new WebUntisAuth($basis, $schule, 'BeispielClient');
$wu->authenticate($argv[1] ?? '', $argv[2] ?? '');
$rest = new WebUntisRest($basis, $schule);
$rest->mitSessionCookie((string)$wu->sessionCookie());
$rest->tokenHolen();
$rest->tenantErmitteln();

foreach (array_slice($wu->getTeachers(), 0, 3) as $l) {
    $r = $rest->get('/WebUntis/api/rest/view/v1/timetable/entries', [
        'start' => date('Y-m-d', strtotime('monday 2 weeks ago')),
        'end'   => date('Y-m-d', strtotime('friday last week')),
        'resourceType' => 'TEACHER', 'resources' => (int)$l['id']]);
    $ex = rest_unterricht_aus_entries($r['json'] ?? []);
    echo $l['name'] . ': ' . implode(', ', array_keys($ex['fachKuerzel'])) . "\n";
}
$wu->logout();
