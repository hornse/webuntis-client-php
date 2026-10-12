<?php
// ============================================================
// tests/sammelskript_test.php – prüft tests/alle.sh
//
// Das Sammelskript ist selbst eine Prüfung; ein Rückschritt dort
// macht jede rote Datei unsichtbar. Geprüft wird an einem eigenen
// Verzeichnis mit Probedateien, NICHT am echten tests/ – sonst
// startete alle.sh diese Datei, und die startete alle.sh.
// ============================================================

declare(strict_types=1);

$fehler = 0;
function pruefe(bool $ok, string $was): void {
    global $fehler;
    echo ($ok ? 'OK  ' : 'FEHLER  ') . $was . "\n";
    if (!$ok) $fehler++;
}

$skript = __DIR__ . '/alle.sh';

/** Startet alle.sh gegen $verz; liefert [exit, ausgabe]. */
function sammeln(string $skript, ?string $verz, string $arg = ''): array {
    $env = $verz === null ? '' : 'TESTVERZEICHNIS=' . escapeshellarg($verz) . ' ';
    $aus = [];
    exec($env . 'bash ' . escapeshellarg($skript) . ($arg !== '' ? ' ' . $arg : '') . ' 2>&1', $aus, $rc);
    return [$rc, implode("\n", $aus)];
}

$tmp = sys_get_temp_dir() . '/wu_alle_' . bin2hex(random_bytes(4));
mkdir($tmp);
$schreibe = fn(string $name, string $code) => file_put_contents("$tmp/$name", "<?php\n$code\n");

// Zwei grüne Dateien in beiden Ausgabeformen des Bestands
$schreibe('a_test.php', 'echo "OK  eins\n"; exit(0);');
$schreibe('b.php',      'echo "  ✓ zwei\n"; exit(0);');

[$rc, $aus] = sammeln($skript, $tmp);
pruefe(str_contains($aus, '2 Testdateien'), 'zwei Probedateien gefunden');
pruefe($rc === 0, 'zwei grüne Dateien: Exit 0');
pruefe(str_contains($aus, '2 Prüfungen bestanden'),
    'Prüfungen beider Ausgabeformen gezählt (OK und ✓)');

// GEGENPROBE: eine NEUE Datei muss gefunden werden, ohne dass
// alle.sh geändert wird - und sie ist rot.
$schreibe('c_test.php', 'echo "FEHLER  drei\n"; exit(1);');
[$rc, $aus] = sammeln($skript, $tmp);
pruefe(str_contains($aus, '3 Testdateien'), 'GEGENPROBE: eine neu angelegte Datei wird gefunden');
pruefe($rc !== 0, 'eine rote Datei macht den Lauf rot');
pruefe((bool)preg_match('/^c_test\.php .*ROT \(Exit 1\)/m', $aus), 'die rote Datei ist benannt');

// Ein Fatal ist rot, nicht still
$schreibe('c_test.php', 'echo "OK  vorher\n"; gibt_es_nicht();');
[$rc, $aus] = sammeln($skript, $tmp);
pruefe($rc !== 0 && str_contains($aus, 'c_test.php') && str_contains($aus, 'ROT'),
    'ein Fatal macht den Lauf rot');

// Null Funde sind ein Fehler: Exit 0 ohne eine einzige Prüfung
$schreibe('c_test.php', 'echo "nichts geprüft\n"; exit(0);');
[$rc, $aus] = sammeln($skript, $tmp);
pruefe($rc !== 0 && str_contains($aus, 'keine Prüfung erkannt'),
    'eine Datei ohne Prüfung macht den Lauf rot');

// Ein leeres Verzeichnis ist kein sauberer Lauf
foreach (glob("$tmp/*.php") ?: [] as $f) unlink($f);
[$rc, $aus] = sammeln($skript, $tmp);
pruefe($rc !== 0 && str_contains($aus, 'keine Testdateien'),
    'ein Verzeichnis ohne Testdatei ist rot');
rmdir($tmp);

// Im echten Verzeichnis: alle.sh ermittelt genau die *.php-Dateien
// hier - gezählt am Verzeichnis, nicht an einer Liste.
[$rc, $aus] = sammeln($skript, null, '--liste');
$soll = count(glob(__DIR__ . '/*.php') ?: []);
pruefe($rc === 0 && str_contains($aus, "$soll Testdateien") && str_contains($aus, basename(__FILE__)),
    "im echten Verzeichnis: alle $soll Testdateien ermittelt, diese eingeschlossen");

echo "\n" . ($fehler === 0 ? 'ALLE TESTS GRÜN' : "$fehler FEHLER") . "\n";
exit($fehler === 0 ? 0 : 1);
