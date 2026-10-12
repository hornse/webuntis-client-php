<?php
// ============================================================
// tests/webuntis_auth_test.php – WebUntisAuth gegen einen Probeserver
//
// Geprüft wird die EIGENSCHAFT, nicht der Quelltext: Die Klasse ruft
// über ihr echtes rpc() (cURL) einen lokalen PHP-Server, der
// JSON-RPC-Antworten nach Drehbuch liefert. Das Drehbuch steht im
// Schulnamen (?school=…), den rpc() ohnehin mitschickt.
//
// Der Server wird HIER gestartet und beendet – ein Prüfschritt, dessen
// Voraussetzung von Hand hergestellt werden muss, würde nie ausgeführt.
//
// Die Antworten sind ERFUNDEN, bis auf zwei belegte Angaben:
//   -8998 und sein Wortlaut: docs/CHANGELOG_ergaenzung.md, v1.5.0
//         (getKlassen ohne Schuljahr, Sommer 2026)
//   -8520 steht hier nur als Beispiel für „ein anderer Fehlercode“;
//         Wortlaut und Bedeutung sind für diese Prüfung ERFUNDEN und
//         unerheblich.
// ============================================================

declare(strict_types=1);

require __DIR__ . '/../src/WebUntisAuth.php';

$fehler = 0;
function pruefe(bool $ok, string $was): void {
    global $fehler;
    echo ($ok ? 'OK  ' : 'FEHLER  ') . $was . "\n";
    if (!$ok) $fehler++;
}

/** Führt $f aus; liefert ['wert' => …] oder ['ausnahme' => Throwable]. */
function versuche(callable $f): array {
    try {
        return ['wert' => $f()];
    } catch (Throwable $e) {
        return ['ausnahme' => $e];
    }
}

// ---- Probeserver ----------------------------------------------------
$tmp = sys_get_temp_dir() . '/wu_auth_' . bin2hex(random_bytes(4));
mkdir($tmp);
$protokoll = "$tmp/anfragen.log";
file_put_contents("$tmp/router.php", <<<'PHP'
<?php
$roh = (string)file_get_contents('php://input');
$req = json_decode($roh, true) ?: [];
file_put_contents(__DIR__ . '/anfragen.log', $roh . "\n", FILE_APPEND);
$fall = $_GET['school'] ?? '';
$methode = $req['method'] ?? '';
$fehler = fn(int $c, string $m) => ['jsonrpc' => '2.0', 'id' => $req['id'] ?? null,
                                    'error' => ['code' => $c, 'message' => $m]];
$ergebnis = fn($r) => ['jsonrpc' => '2.0', 'id' => $req['id'] ?? null, 'result' => $r];
header('Content-Type: application/json');
switch ($fall) {
    case 'ferien':
        echo json_encode($fehler(-8998, 'Cannot invoke "com.grupet.web.basic.Schoolyear.getEndDate()" because "schoolyear" is null'));
        break;
    case 'null':
        echo json_encode($ergebnis(null));
        break;
    case 'abgelaufen':
        echo json_encode($fehler(-8520, 'not authenticated'));
        break;
    case 'keinjson':
        header('Content-Type: text/html');
        echo '<html>Wartung</html>';
        break;
    default:
        if ($methode === 'getCurrentSchoolyear') echo json_encode($ergebnis(['id' => 12, 'name' => '2026/2027']));
        elseif ($methode === 'getSchoolYears')   echo json_encode($ergebnis([['id' => 11], ['id' => 12]]));
        elseif ($methode === 'getKlassen')       echo json_encode($ergebnis([['id' => 42]]));
        else echo json_encode($fehler(-32601, 'Method not found'));
}
PHP);

$port = 0;
$proc = null;
for ($versuch = 0; $versuch < 10 && $proc === null; $versuch++) {
    $p = random_int(20000, 40000);
    $h = proc_open(['php', '-S', "127.0.0.1:$p", "$tmp/router.php"],
                   [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
    for ($i = 0; $i < 50; $i++) {
        $s = @fsockopen('127.0.0.1', $p, $en, $es, 0.1);
        if ($s) { fclose($s); $proc = $h; $port = $p; break; }
        usleep(50000);
    }
    if ($proc === null) proc_terminate($h);
}
pruefe($proc !== null, 'Probeserver läuft (Voraussetzung aller folgenden Prüfungen)');
if ($proc === null) {
    echo "\n1 FEHLER – ohne Probeserver ist nichts geprüft\n";
    exit(1);
}
$basis = "http://127.0.0.1:$port";
$wu = fn(string $fall) => new WebUntisAuth($basis, $fall, 'probe');
$letzteAnfrage = function () use ($protokoll): array {
    $zeilen = is_file($protokoll) ? (file($protokoll, FILE_IGNORE_NEW_LINES) ?: []) : [];
    return json_decode((string)end($zeilen), true) ?: [];
};

// ---- getCurrentSchoolyear: der catch fängt, was der Kommentar sagt --
// Zwischen zwei Schuljahren -> [] ; JEDER andere Fehler -> Ausnahme.
// Vorher fing er jede RuntimeException, und ein Netzfehler sah aus
// wie die Sommerferien.
$r = versuche(fn() => $wu('ferien')->getCurrentSchoolyear());
pruefe(($r['wert'] ?? null) === [], 'getCurrentSchoolyear: -8998 (kein Schuljahr aktiv) ergibt []');

$r = versuche(fn() => $wu('null')->getCurrentSchoolyear());
pruefe(($r['wert'] ?? null) === [], 'getCurrentSchoolyear: Ergebnis null ergibt []');

$r = versuche(fn() => $wu('normal')->getCurrentSchoolyear());
pruefe(($r['wert']['id'] ?? null) === 12, 'getCurrentSchoolyear: ein Schuljahr wird durchgereicht');

$r = versuche(fn() => $wu('abgelaufen')->getCurrentSchoolyear());
pruefe(isset($r['ausnahme']) && $r['ausnahme'] instanceof RuntimeException
       && $r['ausnahme']->getCode() === -8520,
    'getCurrentSchoolyear: ein anderer WebUntis-Fehler (-8520) wird NICHT zu []');

$r = versuche(fn() => $wu('keinjson')->getCurrentSchoolyear());
pruefe(isset($r['ausnahme']) && str_contains($r['ausnahme']->getMessage(), 'kein JSON'),
    'getCurrentSchoolyear: eine Antwort ohne JSON wird NICHT zu []');

// Nicht erreichbar: ein Port, an dem niemand lauscht (der Probeserver
// wird dafür nicht gebraucht).
$frei = 0;
for ($i = 0; $i < 20 && $frei === 0; $i++) {
    $p = random_int(40001, 60000);
    $s = @fsockopen('127.0.0.1', $p, $en, $es, 0.1);
    if ($s) { fclose($s); } else { $frei = $p; }
}
$r = versuche(fn() => (new WebUntisAuth("http://127.0.0.1:$frei", 'x', 'probe'))->getCurrentSchoolyear());
pruefe($frei !== 0 && isset($r['ausnahme']) && str_contains($r['ausnahme']->getMessage(), 'nicht erreichbar'),
    'getCurrentSchoolyear: „nicht erreichbar“ wird NICHT zu []');

// ---- getSchoolyears ---------------------------------------------------
$r = versuche(fn() => $wu('normal')->getSchoolyears());
pruefe(($letzteAnfrage()['method'] ?? '') === 'getSchoolYears',
    'getSchoolyears ruft die JSON-RPC-Methode getSchoolYears (großes Y)');
pruefe(count($r['wert'] ?? []) === 2, 'getSchoolyears reicht die Liste durch');

// ---- getKlassen: mit und ohne Schuljahr -------------------------------
versuche(fn() => $wu('normal')->getKlassen());
$a = $letzteAnfrage();
pruefe(($a['method'] ?? '') === 'getKlassen' && ($a['params'] ?? null) === [],
    'getKlassen() ohne Schuljahr schickt leere params');
versuche(fn() => $wu('normal')->getKlassen(7));
$a = $letzteAnfrage();
pruefe(($a['params'] ?? null) === ['schoolyearId' => 7],
    'getKlassen(7) schickt schoolyearId 7');

// ---- Die Schülerabfrage steht NICHT im Modul --------------------------
// method_exists statt is_callable: Hier ist Abwesenheit gefragt, und
// auch eine private Fassung wäre eine zu viel.
pruefe(!method_exists('WebUntisAuth', 'getStudents'),
    'WebUntisAuth führt keine getStudents() – auch keine private');

// ---- Aufräumen --------------------------------------------------------
proc_terminate($proc);
proc_close($proc);
foreach (glob("$tmp/*") ?: [] as $f) unlink($f);
rmdir($tmp);

echo "\n" . ($fehler === 0 ? 'ALLE TESTS GRÜN' : "$fehler FEHLER") . "\n";
exit($fehler === 0 ? 0 : 1);
