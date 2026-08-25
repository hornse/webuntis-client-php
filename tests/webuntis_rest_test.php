<?php
// Offline-Test für WebUntisRest::baueKopfzeilen() (keine Instanz nötig).
// Prüft, dass eine über setzeKopfzeile() gesetzte Kopfzeile tatsächlich
// im Kopfzeilen-Aufbau aller drei sendenden Wege ankommt – ohne cURL
// aufzurufen, weil baueKopfzeilen() den Aufbau von der Ausführung trennt.
declare(strict_types=1);

require __DIR__ . '/../src/WebUntisRest.php';

$fehler = 0;
function pruefe(bool $ok, string $was): void {
    global $fehler;
    echo ($ok ? 'OK  ' : 'FEHLER  ') . $was . "\n";
    if (!$ok) $fehler++;
}

/** Ruft die private baueKopfzeilen() über Reflection auf. */
function kopfzeilen(WebUntisRest $rest, string $accept, ?string $contentType = null): array
{
    $m = new ReflectionMethod(WebUntisRest::class, 'baueKopfzeilen');
    return $m->invoke($rest, $accept, $contentType);
}

// ------------------------------------------------------------
// 1. Ohne setzeKopfzeile(): Verhalten bleibt wie bisher
// ------------------------------------------------------------
$rest = new WebUntisRest('https://schule.webuntis.com', 'schule');
$ohne = kopfzeilen($rest, 'application/json, text/plain');
pruefe(in_array('Accept: application/json, text/plain', $ohne, true),
    'Accept steht ohne setzeKopfzeile()');
pruefe(count(array_filter($ohne, fn($h) => str_starts_with($h, 'X-'))) === 0,
    'keine eigene Kopfzeile ohne setzeKopfzeile() (Gegenprobe)');

// ------------------------------------------------------------
// 2. Gegenprobe zu 1: mit setzeKopfzeile() erscheint sie tatsächlich
// ------------------------------------------------------------
$mitJahr = new WebUntisRest('https://schule.webuntis.com', 'schule');
$mitJahr->setzeKopfzeile('X-Webuntis-Api-School-Year-Id', '28');

pruefe(in_array('X-Webuntis-Api-School-Year-Id: 28',
        kopfzeilen($mitJahr, 'application/json, text/plain'), true),
    'gesetzte Kopfzeile steht im Aufbau für get()/rohGet()');
pruefe(in_array('X-Webuntis-Api-School-Year-Id: 28',
        kopfzeilen($mitJahr, 'application/json, text/plain', 'application/json'), true),
    'gesetzte Kopfzeile steht im Aufbau für post()');
pruefe(in_array('X-Webuntis-Api-School-Year-Id: 28',
        kopfzeilen($mitJahr, 'application/json, text/plain, */*',
                   'multipart/form-data; boundary=x'), true),
    'gesetzte Kopfzeile steht im Aufbau für postMultipart()');

// ------------------------------------------------------------
// 3. Mehrere eigene Kopfzeilen stehen nebeneinander
// ------------------------------------------------------------
$mehrere = new WebUntisRest('https://schule.webuntis.com', 'schule');
$mehrere->setzeKopfzeile('X-Eins', '1');
$mehrere->setzeKopfzeile('X-Zwei', '2');
$beide = kopfzeilen($mehrere, 'application/json, text/plain');
pruefe(in_array('X-Eins: 1', $beide, true) && in_array('X-Zwei: 2', $beide, true),
    'zwei eigene Kopfzeilen stehen beide');

// ------------------------------------------------------------
// 4. Erneutes Setzen desselben Namens überschreibt, verdoppelt nicht
// ------------------------------------------------------------
$ueberschrieben = new WebUntisRest('https://schule.webuntis.com', 'schule');
$ueberschrieben->setzeKopfzeile('X-Webuntis-Api-School-Year-Id', '27');
$ueberschrieben->setzeKopfzeile('X-Webuntis-Api-School-Year-Id', '28');
$treffer = array_values(array_filter(
    kopfzeilen($ueberschrieben, 'application/json, text/plain'),
    fn($h) => str_starts_with($h, 'X-Webuntis-Api-School-Year-Id')
));
pruefe($treffer === ['X-Webuntis-Api-School-Year-Id: 28'],
    'erneutes Setzen überschreibt statt zu verdoppeln');

// ------------------------------------------------------------
// 5. Cookie bleibt letzte Kopfzeile, auch mit eigener Kopfzeile gesetzt
// ------------------------------------------------------------
$mitCookie = new WebUntisRest('https://schule.webuntis.com', 'schule');
$mitCookie->mitSessionCookie('JSESSIONID=abc');
$mitCookie->setzeKopfzeile('X-Test', 'x');
$reihenfolge = kopfzeilen($mitCookie, 'application/json, text/plain');
pruefe(end($reihenfolge) === 'Cookie: JSESSIONID=abc; schoolname=_' . base64_encode('schule'),
    'Cookie bleibt letzte Kopfzeile');

echo "\n";
if ($fehler === 0) {
    echo "ALLE TESTS GRÜN\n";
    exit(0);
}
echo "$fehler FEHLER\n";
exit(1);
