<?php
// ============================================================
// tests/extractors_lehrkraefte_test.php
// Tests für rest_lehrkraefte_aus_entries() und rest_konto_aus_appdata()
// (hornse/webuntis-client-php v1.2.0)
//
// Aufruf: php tests/extractors_lehrkraefte_test.php
// Exit-Code 0 = alles grün. Kein Netz, keine DB nötig.
// ============================================================

declare(strict_types=1);

require __DIR__ . '/../src/extractors.php';

$fehler = 0;
function pruefe(string $name, bool $ok): void
{
    global $fehler;
    echo ($ok ? '  ✓ ' : '  ✗ ') . $name . "\n";
    if (!$ok) $fehler++;
}

// Hilfsfunktion: baut einen Stundenplan-Eintrag im Format der
// echten Antwort (Befund frg-dusseldorf 07/2026, format 19).
function eintrag(string $status, array $lehrer, string $fach = 'M',
                 string $typ = 'NORMAL_TEACHING_PERIOD'): array
{
    $pos2 = [];
    foreach ($lehrer as $kuerzel => $langname) {
        $pos2[] = ['current' => ['type' => 'TEACHER', 'shortName' => $kuerzel,
                                 'longName' => $langname, 'status' => $status]];
    }
    return [
        'type' => $typ, 'status' => $status,
        'position1' => [['current' => ['type' => 'SUBJECT', 'shortName' => $fach,
                                       'longName' => $fach]]],
        'position2' => $pos2,
        'position3' => [['current' => ['type' => 'ROOM', 'shortName' => 'A101']]],
    ];
}

echo "rest_lehrkraefte_aus_entries – Filterregeln\n";
$plan = ['format' => 19, 'days' => [['date' => '2026-06-15', 'gridEntries' => [
    eintrag('REGULAR',      ['Gr' => 'Greitemann']),
    eintrag('REGULAR',      ['Gr' => 'Greitemann'], 'D'),
    eintrag('CANCELLED',    ['Kl' => 'Klein'], 'E'),
    eintrag('SUBSTITUTION', ['Ve' => 'Vertretung'], 'M'),
    eintrag('CHANGED',      ['Ch' => 'Changed'], 'M'),
    eintrag('REGULAR',      ['Au' => 'Aufsicht'], '', 'BREAK_SUPERVISION'),
    eintrag('REGULAR',      ['Ev' => 'Event'], '', 'EVENT'),
    eintrag('REGULAR',      ['Ex' => 'Klausur'], 'M', 'EXAM'),
]]]];
$ex = rest_lehrkraefte_aus_entries($plan);
$kuerzel = array_keys($ex['lehrkraefte']);

pruefe('Vertretung (SUBSTITUTION) NICHT gewertet', !in_array('Ve', $kuerzel, true));
pruefe('CHANGED NICHT gewertet',                   !in_array('Ch', $kuerzel, true));
pruefe('Pausenaufsicht NICHT gewertet',            !in_array('Au', $kuerzel, true));
pruefe('EVENT NICHT gewertet',                     !in_array('Ev', $kuerzel, true));
// Ab v1.6.0: EXAM/REGULAR wird bewusst gewertet (Fachlehrkraft
// beaufsichtigt eigene Arbeiten). Abschaltbar über den zweiten Parameter.
pruefe('EXAM/REGULAR wird gewertet (ab v1.6.0)', in_array('Ex', $kuerzel, true));
pruefe('EXAM zählt NICHT als Unterrichtsstunde',
    ($ex['lehrkraefte']['Ex']['stunden'] ?? -1) === 0);
pruefe('EXAM getrennt gezählt',
    ($ex['lehrkraefte']['Ex']['klausuren'] ?? 0) === 1);
pruefe('abschaltbar über zweiten Parameter',
    !isset(rest_lehrkraefte_aus_entries($plan, false)['lehrkraefte']['Ex']));
pruefe('Ausfall (CANCELLED) GEWERTET',              in_array('Kl', $kuerzel, true));
pruefe('reguläre Lehrkraft gewertet',               in_array('Gr', $kuerzel, true));

echo "rest_lehrkraefte_aus_entries – Auswertung\n";
pruefe('Stunden gezählt (Gr: 2)',   $ex['lehrkraefte']['Gr']['stunden'] === 2);
pruefe('nach Stunden sortiert',     $kuerzel[0] === 'Gr');
pruefe('Fächer je Lehrkraft',
    array_keys($ex['lehrkraefte']['Gr']['faecher']) === ['M', 'D']);
pruefe('Fachhäufigkeit gezählt',    $ex['lehrkraefte']['Gr']['faecher']['M'] === 1);
pruefe('Langname übernommen',       $ex['lehrkraefte']['Gr']['name'] === 'Greitemann');
pruefe('gewertete Einträge gezählt (3 Unterricht + 1 Klausur)',
    $ex['eintraege'] === 4);

echo "rest_lehrkraefte_aus_entries – Sonderfälle\n";
// Kopplung: zwei Lehrkräfte in einem Eintrag
$kopplung = ['days' => [['gridEntries' => [
    eintrag('REGULAR', ['Gr' => 'Greitemann', 'Kl' => 'Klein'], 'SP'),
]]]];
$exK = rest_lehrkraefte_aus_entries($kopplung);
pruefe('Kopplung: beide Lehrkräfte erfasst', count($exK['lehrkraefte']) === 2);
pruefe('Kopplung: beide bekommen das Fach',
    isset($exK['lehrkraefte']['Gr']['faecher']['SP'],
          $exK['lehrkraefte']['Kl']['faecher']['SP']));

// removed wird ignoriert – nur current zählt
$mitRemoved = ['days' => [['gridEntries' => [[
    'type' => 'NORMAL_TEACHING_PERIOD', 'status' => 'REGULAR',
    'position1' => [['current' => ['type' => 'SUBJECT', 'shortName' => 'M']]],
    'position2' => [['current' => ['type' => 'TEACHER', 'shortName' => 'Neu'],
                     'removed' => ['type' => 'TEACHER', 'shortName' => 'Weg']]],
]]]]];
pruefe('removed ignoriert, current gewertet',
    array_keys(rest_lehrkraefte_aus_entries($mitRemoved)['lehrkraefte']) === ['Neu']);

// Eintrag ohne Lehrkraft (z. B. nur Raumbuchung)
$ohneLehrer = ['days' => [['gridEntries' => [[
    'type' => 'NORMAL_TEACHING_PERIOD', 'status' => 'REGULAR',
    'position1' => [['current' => ['type' => 'SUBJECT', 'shortName' => 'M']]],
]]]]];
pruefe('Eintrag ohne Lehrkraft zählt nicht',
    rest_lehrkraefte_aus_entries($ohneLehrer)['eintraege'] === 0);

// Kein Status im Eintrag -> als REGULAR behandeln
$ohneStatus = ['days' => [['gridEntries' => [[
    'type' => 'NORMAL_TEACHING_PERIOD',
    'position1' => [['current' => ['type' => 'SUBJECT', 'shortName' => 'M']]],
    'position2' => [['current' => ['type' => 'TEACHER', 'shortName' => 'Oh']]],
]]]]];
pruefe('fehlender Status = REGULAR',
    isset(rest_lehrkraefte_aus_entries($ohneStatus)['lehrkraefte']['Oh']));

// Leere und kaputte Eingaben
pruefe('leere Antwort',    rest_lehrkraefte_aus_entries([])['lehrkraefte'] === []);
pruefe('String-Eingabe',   rest_lehrkraefte_aus_entries('x')['lehrkraefte'] === []);
pruefe('null-Eingabe',     rest_lehrkraefte_aus_entries(null)['lehrkraefte'] === []);
pruefe('NO_DATA-Tage',     rest_lehrkraefte_aus_entries(
    ['days' => [['date' => '2026-06-15', 'status' => 'NO_DATA',
                 'gridEntries' => []]]])['lehrkraefte'] === []);

echo "rest_konto_aus_appdata\n";
// Echte Struktur eines Eltern-Kontos (Sondierung 07/2026)
$eltern = ['user' => [
    'id' => 5984,
    'name' => 'eltern@example.org',
    'person' => ['id' => 476, 'displayName' => 'Muster Max'],
    'roles' => ['LEGAL_GUARDIAN'],
    'students' => [
        ['id' => 13914, 'displayName' => 'Muster Paul'],
        ['id' => 14069, 'displayName' => 'Muster Petra'],
    ],
]];
$k = rest_konto_aus_appdata($eltern);
pruefe('userId (Mitteilungs-Adressat)', $k['userId'] === 5984);
pruefe('personId getrennt von userId',  $k['personId'] === 476);
pruefe('Rolle LEGAL_GUARDIAN',          $k['rollen'] === ['LEGAL_GUARDIAN']);
pruefe('zwei Kinder erkannt',           count($k['kinder']) === 2);
pruefe('Kind-ID korrekt',               $k['kinder'][0]['id'] === 13914);
pruefe('Kind-Name übernommen',          $k['kinder'][0]['name'] === 'Muster Paul');
pruefe('user.person ist KEIN Kind',
    !in_array(476, array_column($k['kinder'], 'id'), true));

$lehrkraft = ['user' => ['id' => 730, 'person' => ['id' => 1013],
    'roles' => ['TEACHER'], 'students' => []]];
pruefe('Lehrkraft hat keine Kinder', rest_konto_aus_appdata($lehrkraft)['kinder'] === []);

$admin = ['user' => ['id' => 568, 'person' => ['id' => -1],
    'students' => [['id' => -1, 'displayName' => '']]]];
pruefe('Admin-Platzhalter -1 verworfen', rest_konto_aus_appdata($admin)['kinder'] === []);

pruefe('robust ohne user-Objekt', rest_konto_aus_appdata([])['userId'] === null);
pruefe('robust bei String',       rest_konto_aus_appdata('x')['kinder'] === []);

echo "\n" . ($fehler === 0 ? "ALLE TESTS GRÜN\n" : "$fehler TEST(S) ROT\n");
exit($fehler === 0 ? 0 : 1);
