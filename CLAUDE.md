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

**Was über die WebUntis-API gemessen ist, liegt verstreut.** Die Liste
aller Fundstellen steht in
`~/Projekte/koordination/docs/BEFUND-2026-10-11-webuntis-api-bestand.md`
— 846 Stellen in zehn Repos, Stand 11.10.2026. Sie sagt, **wo** etwas
steht, nicht was gilt. **Vor jedem neuen Aufruf dort nachsehen**, nicht
nur im eigenen Repo.

**Die Fundstellen widersprechen sich** (Abschnitt 4 der Liste), darunter:
Die Felder von `getStudents` werden in mehreren Fassungen beschrieben;
für den Mitteilungsversand nennen Modul-Doku und Modul-Code verschiedene
Wege; die Schülerzahl aus `getStudents` steht je nach Datum bei 3563,
3782 oder 3802. **Und keine Messung trägt die WebUntis-Version** — was
vor Wochen gemessen wurde, kann ein Update überholt haben.

## Was das Repo ist

**Dies ist eine Quelle, kein Projekt.** Es liefert den WebUntis-Zugriff
und wird in die Anwendungen **vendored** — kopiert, nicht eingebunden.

| | |
|---|---|
| Inhalt | `src/WebUntisRest.php`, `src/WebUntisAuth.php`, `src/extractors.php` |
| Remote | `github` → **`hornse/webuntis-client-php`** |
| Testskripte | `bash tests/alle.sh` startet **alle** `tests/*.php`, jede einzeln (seit v2.0.0) |
| Tags | ab `v2.0.0` (12.10.2026); die Nummern 1.x davor sind nie getaggt, siehe `CHANGELOG.md` |
| Lizenz | GPL-3.0-or-later |

## Der Verzeichnisname weicht vom Repo-Namen ab

Das Verzeichnis heißt `webuntis_client_php` mit Unterstrichen, das Repo
`webuntis-client-php` mit Bindestrichen. Wer über Verzeichnisnamen auf
Repo-Namen schließt, liegt hier falsch — der Bestandslauf löst deshalb
über die Git-Remotes auf, nicht über den Ordner.

## Was offen ist

**Die Verteilung von v2.0.0 in die Projekte steht aus.** Zusammengeführt
ist im Modul (12.10.2026); die Kopien in den Projekten sind unverändert,
und der Bestandslauf meldet deshalb weiter fünf Kopien, die keinem Stand
dieser Historie entsprechen (`WebUntisAuth` in lernzeiten, signage,
sprechtag; `extractors.php` in lernzeiten, sprechtag). Die Verteilung ist
ein eigener Auftrag. Was sie je Projekt ändert, steht im Bericht des
Zusammenführungslaufs (`docs/BERICHT-2026-10-12-zusammenfuehrung.md`).

**Die Schülerabfrage `getStudents()` steht absichtlich nicht im Modul**
(personenbezogen, REIHENREGELN 9). Die Kopien von `WebUntisAuth` in
lernzeiten, signage und sprechtag führen sie; nur die Sondierung in
sprechtag ruft sie. Wer verteilt, muss das dort klären.

**Die Nummern 1.x sind Behauptungen.** Sie stehen in Commit-Titeln,
Kopfvermerken und Dateinamen, nie als Tag. Was sie bezeichnet haben,
steht in `CHANGELOG.md` unter `[2.0.0]`. „1.0.0" bezeichnet den Stand vom
17.07.2026 — deshalb heißt der erste Tag `v2.0.0`.

**`WebUntisAuth` gibt es zweimal in der Reihe.** Auch
`hornse/webuntis-auth-php` deklariert eine Klasse dieses Namens im
globalen Namensraum; zwei `require` im selben Request sind ein Fatal.
