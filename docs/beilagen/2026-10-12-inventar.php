<?php
// Beilage zum Befund vom 12.10.2026: listet je PHP-Datei Kopfvermerk, Namensraum,
// Klassen und Funktionen mit Rumpf-Prüfsumme. Aufruf: php inventar.php DATEI...
// Listet je Datei: Kopfvermerk, Klassen/Namensraum, Funktionen mit Sichtbarkeit und
// Rumpf-Prüfsumme (Kommentare und Leerraum entfernt, über den Tokenizer).
foreach (array_slice($argv, 1) as $datei) {
    $src = file_get_contents($datei);
    $t = token_get_all($src);
    $kopf = [];
    foreach (preg_split('/\R/', $src) as $i => $z) if (stripos($z, 'VENDORED') !== false) $kopf[] = ($i+1) . ': ' . trim($z);
    $ns = ''; $klassen = []; $funk = [];
    $n = count($t);
    for ($i = 0; $i < $n; $i++) {
        $tok = $t[$i];
        if (!is_array($tok)) continue;
        if ($tok[0] === T_NAMESPACE) { $j=$i+1; $s=''; while ($j<$n && $t[$j] !== ';' && $t[$j] !== '{') { $s .= is_array($t[$j]) ? $t[$j][1] : $t[$j]; $j++; } $ns = trim($s); }
        if ($tok[0] === T_CLASS) { $j=$i+1; while (is_array($t[$j]) && $t[$j][0]===T_WHITESPACE) $j++; if (is_array($t[$j]) && $t[$j][0]===T_STRING) $klassen[] = $t[$j][1]; }
        if ($tok[0] === T_FUNCTION) {
            $j=$i+1; while (is_array($t[$j]) && $t[$j][0]===T_WHITESPACE) $j++;
            if (!is_array($t[$j]) || $t[$j][0]!==T_STRING) continue; // Closure
            $name = $t[$j][1];
            // Sichtbarkeit rückwärts
            $vis=''; $k=$i-1; while ($k>=0 && is_array($t[$k]) && in_array($t[$k][0],[T_WHITESPACE,T_PUBLIC,T_PRIVATE,T_PROTECTED,T_STATIC,T_FINAL,T_ABSTRACT],true)) { if ($t[$k][0]!==T_WHITESPACE) $vis = $t[$k][1].' '.$vis; $k--; }
            // Signatur + Rumpf
            $sig=''; $m=$j; while ($m<$n && $t[$m] !== '{' && $t[$m] !== ';') { $sig .= is_array($t[$m]) ? ($t[$m][0]===T_WHITESPACE?' ':$t[$m][1]) : $t[$m]; $m++; }
            $tiefe=0; $rumpf='';
            for (; $m<$n; $m++) { $x=$t[$m]; $txt = is_array($x)?$x[1]:$x;
                if ($x==='{' || (is_array($x) && in_array($x[0],[T_CURLY_OPEN,T_DOLLAR_OPEN_CURLY_BRACES],true))) $tiefe++;
                if ($x==='}') $tiefe--;
                if (!(is_array($x) && in_array($x[0],[T_COMMENT,T_DOC_COMMENT,T_WHITESPACE],true))) $rumpf .= $txt;
                if ($tiefe===0) break; }
            $funk[] = sprintf('%-10s %-28s %s  %s', trim($vis)?:'-', $name, substr(sha1($rumpf),0,8), preg_replace('/\s+/',' ',trim($sig)));
        }
    }
    echo "### $datei\n  sha1 " . substr(sha1($src),0,12) . "  Zeilen " . substr_count($src,"\n") . "\n";
    echo "  Vermerk: " . ($kopf ? implode(' | ', $kopf) : 'KEINER') . "\n";
    echo "  Namensraum: " . ($ns ?: '(global)') . "  Klassen: " . implode(',', $klassen) . "\n";
    foreach ($funk as $f) echo "    $f\n";
}
