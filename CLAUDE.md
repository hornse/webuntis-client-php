# Projektgedächtnis: webuntis-client-php

Diese Datei wird von Claude Code bei **jedem** Sessionstart automatisch
gelesen. Sie liegt im Repo und wird per Push geteilt. Was hier steht,
muss nicht mehr erklärt werden.

Nicht hier hinein gehören Tagesaufgaben. Hier stehen Dinge, die in sechs
Monaten noch gelten sollen.

Ergänzend:
- Regeln der Reihe: @REIHENREGELN.md
- Fallstricke PHP/Router/WebUntis: @FALLSTRICKE.md

---

## Was hier nicht steht

**Die Regeln der Reihe stehen in `REIHENREGELN.md`**, die technischen
Fallstricke in `FALLSTRICKE.md`. Beide sind vendorte Kopien aus
`hornse/koordination` und oben importiert. `FALLSTRICKE.md` ist hier
besonders einschlägig: Was dort über Schuljahres-IDs, Fehlercodes und
die Auswertung fremder Antworten steht, beschreibt genau das, was dieses
Repo umsetzt.

## Was das Repo ist

**Dies ist eine Quelle, kein Projekt.** Es liefert den WebUntis-Zugriff
und wird in die Anwendungen **vendored** — kopiert, nicht eingebunden.

| | |
|---|---|
| Inhalt | `src/WebUntisRest.php`, `src/WebUntisAuth.php`, `src/extractors.php` |
| Remote | `github` → **`hornse/webuntis-client-php`** |
| Testskripte | vier Dateien in `tests/`, **zwei davon rot** — siehe „Was offen ist" |
| Lizenz | GPL-3.0-or-later |

## Der Verzeichnisname weicht vom Repo-Namen ab

Das Verzeichnis heißt `webuntis_client_php` mit Unterstrichen, das Repo
`webuntis-client-php` mit Bindestrichen. Wer über Verzeichnisnamen auf
Repo-Namen schließt, liegt hier falsch — der Bestandslauf löst deshalb
über die Git-Remotes auf, nicht über den Ordner.

## Was offen ist

**Zwei der vier Testdateien sind rot, und zwar seit sie aufgenommen
wurden.** Stand 24.09.2026, jede Datei einzeln mit `php` aufgerufen:

| Datei | Ergebnis |
|---|---|
| `tests/run.php` | grün (18 Prüfungen) |
| `tests/webuntis_rest_test.php` | grün (8 Prüfungen) |
| `tests/extractors_lehrkraefte_test.php` | **rot**: Fatal, `rest_lehrkraefte_aus_entries()` undefiniert |
| `tests/extractors_klausuren_test.php` | **rot**: dito |

Die Funktion steht nur in den Ergänzungsdateien und nicht in
`src/extractors.php`. Die Tests sind hereingekommen, der Code nicht.
**Die roten Tests werden nicht einzeln repariert.** Sie gehören zur
Zusammenführung von `WebUntisAuth` und `extractors.php` (unten).
**Kein Skript lässt alle vier laufen**, und die README nennt nur die
beiden grünen. Wer nur „die Tests" laufen lässt, sieht deshalb grün.
Befund: `hornse/koordination`, `docs/BEFUND-2026-09-24-webuntis-holen.md`.

**Dieses Repo führt keine Tags.** Ein Rückstand einer Kopie lässt sich
deshalb nur auf ein **Datum** beziehen, nicht auf eine Versionsnummer.
Erschwerend: Die Nummern in den Commit-Meldungen dieses Repos passen
nachweislich nicht durchgängig zum Inhalt. Tags nachzutragen ist hier
schwieriger als bei `ci-css` und ein eigener Vorgang.

**`src/` enthält Ergänzungsdateien** (`*_ergaenzung_v*.php`, `.md`) neben
den eigentlichen Quelldateien. Sie liegen im selben Verzeichnis und
werden dadurch vom Bestandslauf als Moduldateien mitgeführt.

**`WebUntisAuth` und `extractors.php` sind in mehreren Anwendungen
auseinandergelaufen.** Das ist belegt, nicht vermutet: Der Bestandslauf
meldet fünf Kopien, die keinem Stand dieser Historie entsprechen. Die
Zusammenführung ist ein eigener Vorgang — nicht in einem Rutsch.

**`WebUntisAuth` gibt es zweimal in der Reihe.** Auch
`hornse/webuntis-auth-php` deklariert eine Klasse dieses Namens im
globalen Namensraum; zwei `require` im selben Request sind ein Fatal.
