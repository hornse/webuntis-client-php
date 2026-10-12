# Befund: Der Bestand des Client-Moduls

Ergebnis von `hornse/koordination`, `docs/AUFTRAG-webuntis-client-bestand.md`
(Stand des Auftrags: 11.10.2026). **Gemessen am 12.10.2026.**

**Nur gelesen.** Nichts zusammengeführt, keine Testdatei repariert, keine
vendorte Kopie angefasst, kein Aufruf gegen WebUntis. Die Probeläufe in
Abschnitt 3 liefen in einem Probebaum außerhalb des Repos.

Werkzeuge, mit denen gemessen wurde (Beilagen, wiederholbar):

* `docs/beilagen/2026-10-12-inventar.php` — je PHP-Datei: Kopfvermerk,
  Namensraum, Klassen, Funktionen mit Sichtbarkeit und einer
  **Rumpf-Prüfsumme** (über den Tokenizer, Kommentare und Leerraum
  entfernt). Gleiche Prüfsumme heißt gleicher Code, unabhängig vom
  Kopfvermerk.
* `docs/beilagen/2026-10-12-probelauf.sh` — der Probebaum aus Abschnitt 3.

---

## 1 — Ausgangsstand

### `git status`

Der Bestand wurde ermittelt, nicht aufgezählt: Suche über `~/Projekte`
nach den Deklarationen `class WebUntisAuth`, `class WebUntisRest` und den
fünf `rest_*`-Funktionen. Vorweg `pwd` und `ls -d`; die Suche fand die
bekannten Dateien im Modul selbst (Gegenprobe, muss treffen).

| Repo | Remote | letzter Commit | Status |
|---|---|---|---|
| webuntis_client_php (Modul) | `github` → hornse/webuntis-client-php | fa8b44b, 11.10. | sauber (vor diesem Befund) |
| fachkonferenzen | `github` | 2263602, 11.10. | sauber |
| kurse | `github` | d8ad202, 11.10. | sauber |
| lernzeiten | `github` | 1a32748, 12.10. | **unsauber:** `?? werkzeuge/__pycache__/`, `?? werkzeuge/ein_fall_suchen.py` |
| projektstunden | `github` → projektstunden-frg | 42b9bd2, 11.10. | **unsauber:** `?? sql/gen/__pycache__/` |
| schulprozesse | `github` | 92aeadf, 11.10. | sauber |
| signage | `github` | 414f5d5, 11.10. | sauber |
| sprechtag | `github` | 04251e5, 12.10. | **unsauber:** ` M docs/MITTEILUNGEN.md` |
| webuntis-auth-php | **`origin`** (nicht `github`) | 79caf93, 11.10. | sauber |

**Gemeldet, nicht angefasst.** Die Änderung an `sprechtag/docs/MITTEILUNGEN.md`
betrifft genau das Thema von Abschnitt 4 — dort wird offenbar parallel
gearbeitet. Was in ihr steht, ist **nicht** in diesen Befund eingegangen;
gelesen wurde der Code.

### Die Testskripte, einzeln

Jede Datei einzeln mit `php`, Exit-Code und Zahl der Prüfungen:

| Datei | Exit | Ergebnis |
|---|---:|---|
| `tests/run.php` | 0 | grün, **23** Prüfungen |
| `tests/webuntis_rest_test.php` | 0 | grün, 8 Prüfungen |
| `tests/extractors_lehrkraefte_test.php` | 255 | **Fatal**: `rest_lehrkraefte_aus_entries()` undefiniert (Zeile 53) |
| `tests/extractors_klausuren_test.php` | 255 | **Fatal**: dito (Zeile 29) |

**Abweichung vom zuletzt Bekannten:** `CLAUDE.md` nennt für `run.php`
**18** Prüfungen (Stand 24.09.). Gemessen sind **23**. Die Erklärung steht
in der Historie: `7bc46cb` (24.09., *„setzeTimeout() ist keine optionale
Methode"*) hat `tests/run.php` erweitert — die letzten fünf Zeilen der
Ausgabe (die acht zugesagten Methoden, die Zusage in der README). Die
Zahl in `CLAUDE.md` ist also älter als der letzte Commit an der Suite;
die Prüfungszahl ist **gestiegen, nicht gesunken**. Nicht berichtigt —
`CLAUDE.md` zu ändern liegt nicht im Auftrag.

### Welches Skript sie alle startet

**Keines.** Gesucht in `*.sh`, `Makefile`, `composer.json` (kein
`scripts`-Eintrag), `.github/` (gibt es nicht). Die README nennt in
Zeile 141–142 nur die beiden grünen Dateien. `EINFUEGEN.md` Zeile 52–53
nennt die beiden roten — als Schritt **nach** dem Einfügen der
Ergänzung, der nie stattgefunden hat.

**Das ist der Grund, warum die roten unbemerkt blieben:** Wer der README
folgt, sieht grün. Wer `EINFUEGEN.md` folgt, hätte vorher einfügen
müssen.

---

## 2 — Die Fassungen

### Zwei Klassen gleichen Namens, zwei Familien

`WebUntisAuth` steht in **neun** Dateien. Nach Konstruktor und Aufgabe
sind es zwei Familien, die nur den Klassennamen teilen:

| Familie | Konstruktor | Aufgabe | Quelle |
|---|---|---|---|
| **A** — Datenabruf | `(baseUrl, school, client)` | JSON-RPC: Lehrkräfte, Fächer, Räume, Stundenplan | **dieses Modul** |
| **B** — Anmeldung | `(PDO $db, array $config)` | Login mit Bremse, Protokoll, Namensauflösung | `hornse/webuntis-auth-php` |

Rumpf-Prüfsummen je Methode (Auszug, vollständig mit dem Inventar
wiederholbar):

**Familie A**

| Fundstelle | Datei-sha1 | Zeilen | Kopfvermerk | Methoden |
|---|---|---:|---|---|
| Modul `src/` | 50920e44e9f8 | 129 | **keiner** (Quelle) | 8 öffentliche + `rpc` |
| fachkonferenzen | 50920e44e9f8 | 129 | **keiner** | byteweise gleich dem Modul |
| lernzeiten | 93c95c3cc5e5 | 169 | `hornse/webuntis-client-php`, **ohne Version** | Modul + `getKlassen`, `getSchoolyears`, `getCurrentSchoolyear`, `getStudents` |
| signage | 93c95c3cc5e5 | 169 | dito | dito |
| sprechtag | 93c95c3cc5e5 | 169 | dito | dito |

Die drei 169-Zeilen-Fassungen sind byteweise gleich. Gegen das Modul
weichen sie in **genau zwei** Dingen ab (`diff`):

1. die vier Zusatzmethoden — wortgleich mit dem Code in
   `src/WebUntisAuth_ergaenzung_v1.5.0.md`, bis auf die Kommentare zu
   `getStudents` (siehe unten);
2. in `rpc()` das Präfix der Anfrage-ID: `uniqid('spr_')` statt
   `uniqid('fako_')`. Ohne Wirkung auf den Aufruf, aber ein Herkunftsbeleg:
   Die Fassung stammt aus `sprechtag`.

**Alle elf gemeinsamen Methoden haben dieselbe Rumpf-Prüfsumme**, außer
`rpc` (das Präfix). **Gleichnamige Methoden, die Verschiedenes tun, gibt
es in Familie A nicht.**

Die Moduldatei `src/WebUntisAuth.php` hat in der Historie **einen**
Stand (9018d5c, 17.07.2026). Die 169-Zeilen-Fassung entspricht keinem
Stand der Historie — sie ist Modul plus Anleitungsdatei, nie
zurückgeflossen.

**Familie B**

| Fundstelle | Datei-sha1 | Zeilen | Kopfvermerk | Methoden |
|---|---|---:|---|---|
| webuntis-auth-php `src/` | 6c78c3589de2 | 388 | keiner (Quelle) | `authenticate(user, pass, ip)`, `isLocked`, `sessionCookie`, … |
| kurse | 051d1f26c2d2 | 389 | `hornse/webuntis-auth-php v1.2.0` | Code gleich der Quelle; die Zeile mehr ist der Vermerk |
| projektstunden | 567d7c62a4cb | 255 | **keiner** | eigene Fassung: `authenticateAndGetDetails`, `logoutSession`, `jsonRpc` **private** |
| schulprozesse | 332e715d8a4f | 222 | **keiner** | Namensraum `App\Auth`; `callJsonRpc`, `logAttempt` |

**Hier gibt es gleichnamige Methoden mit verschiedenem Inhalt:**
`authenticate` hat in B0, projektstunden und schulprozesse drei
verschiedene Rumpf-Prüfsummen (9e90e97b, 5a47253e, c8a611d1);
`tooManyAttempts` ebenso. Ob sie dasselbe **tun**, sagt die Prüfsumme
nicht — das war Gegenstand der Erhebung vom 09.09.

`webuntis-auth-php` führt **keine Tags**; die Nummer `v1.2.0` im Vermerk
von `kurse` ist damit nicht belegbar.

### `WebUntisRest` — nicht auseinandergelaufen

| Fundstelle | Kopfvermerk | Code |
|---|---|---|
| Modul `src/` | keiner (Quelle) | 15 Methoden |
| fachkonferenzen, lernzeiten, sprechtag | `… v1.7.0 – dort ändern …` | **alle 15 Rumpf-Prüfsummen gleich dem Modul** |
| kurse | `… v1.7.0 …` (in Zeile 2 statt 4) | dito |

Nichts zusammenzuführen.

### `extractors.php` — gleicher Name, **verschiedene Datei**

| Fundstelle | Datei-sha1 | Kopfvermerk | Funktionen |
|---|---|---|---|
| Modul `src/` | 3d9175787039 | `Teil von hornse/webuntis-client-php` | `rest_unterricht_aus_entries`, `rest_erster_eintrag`, `rest_paare_aus_weekly` |
| fachkonferenzen | df6fb259f66c | `VENDORED aus hornse/webuntis-client-php – dort ändern …` (**ohne Version**) | dieselben drei, Code gleich |
| lernzeiten | 482e9ac32452 | `VENDORED aus hornse/webuntis-client-php v1.2.0` | `rest_lehrkraefte_aus_entries($json, bool $mitKlausuren = true)`, `rest_konto_aus_appdata` |
| sprechtag | 482e9ac32452 | dito | dito |

**Das ist der wichtigste Einzelbefund dieses Abschnitts.** Die Datei, die
in `lernzeiten` und `sprechtag` `extractors.php` heißt, hat **keine
einzige Funktion** mit der Moduldatei gemeinsam. Sie ist inhaltlich
`src/extractors_ergaenzung_v1.6.0.php` (Rumpf-Prüfsumme b63e625e bzw.
52d9e0d9, gleich), mit eigenem Dateikopf.

**Und der Vermerk nennt die falsche Version:** `v1.2.0`, der Code ist der
von 1.6.0 (zweiter Parameter `$mitKlausuren`). Die Fassung 1.2.0 hätte
die Signatur `($json)`. Zudem fehlt dem Vermerk die Form `– dort ändern,
hierher kopieren!` in einer Zeile (sie steht umbrochen in Zeile 5).

`kurse` und `signage` führen keine `extractors.php`.

### Fünf Fassungen einer Datei oder zwei Familien?

**Die Erhebung vom 09.09. ist bestätigt**, Zahl für Zahl: neun Fundstellen
`WebUntisAuth` mit fünf Inhalten (A0, A+, B0, B′, B″), vier Fundstellen
`extractors.php` mit zwei Inhalten (E0, E+). Seitdem hat sich an keiner
dieser Dateien etwas geändert.

**Aber „fünf" bezeichnet zwei verschiedene Dinge,** und das ist bisher
nicht auseinandergehalten worden:

| Wer sagt „fünf" | Was gezählt ist |
|---|---|
| `CLAUDE.md` dieses Repos, Auftrag Abschnitt 2 | **fünf Fundstellen** `fassung-abweichend` des Bestandslaufs: 3 × `WebUntisAuth` (A+) und 2 × `extractors.php` (E+) — **zwei** Inhalte |
| Erhebung 09.09. | **fünf Inhalte** von `WebUntisAuth` über **beide** Familien |

**Was dieses Modul betrifft, sind es zwei Abweichungen, keine fünf:** A+
und E+. Beide sind Modul **plus** Anleitungsdatei, keine Gegenentwicklung.
B′ und B″ gehören zu `webuntis-auth-php` und nicht hierher.

---

## 3 — Die Ergänzungsdateien

### Was da liegt, und seit wann

| Datei | eingeführt | Commit | Inhalt |
|---|---|---|---|
| `src/extractors_ergaenzung_v1.2.0.php` | 23.07.2026 | 2e11a4e („v1.3.0") | `rest_lehrkraefte_aus_entries($json)`, `rest_konto_aus_appdata` |
| `src/WebUntisRest_ergaenzung_v1.3.0.md` | 23.07.2026 | 2e11a4e | `setzeTimeout()`, `post()` als Anleitung |
| `src/WebUntisAuth_ergaenzung_v1.5.0.md` | 24.07.2026 | 2b6838a („v1.5.0") | `getKlassen`, `getStudents`, `getSchoolyears`, `getCurrentSchoolyear` |
| `src/extractors_ergaenzung_v1.6.0.php` | 24.07.2026 | 5eb22f4 („v1.6.0") | wie 1.2.0, `rest_lehrkraefte_aus_entries` mit `$mitKlausuren` |
| `docs/README_ergaenzung.md`, `docs/CHANGELOG_ergaenzung.md` | 23.07., geändert 24.07. | 2e11a4e, 2b6838a, 5eb22f4 | Doku zu allen vier |
| `EINFUEGEN.md` | 23.07., geändert 24.07. | dito | Anleitung, wohin was gehört |

**Sechs Dateien im Code- und Doku-Bereich, vier davon in `src/`.** Die
beiden Testdateien kamen mit denselben Commits (2e11a4e, 5eb22f4).

Kein Commit hat seit dem 24.07. eine dieser Dateien berührt. Die Nummern
in den Commit-Titeln passen nicht zu den Dateinamen (eine `v1.2.0`-Datei
im „v1.3.0"-Commit) — wie in `CLAUDE.md` vermerkt.

### Welche Funktionen in den Moduldateien fehlen

| Funktion | steht in | fehlt in | Code bereits zurückgeflossen? |
|---|---|---|---|
| `rest_lehrkraefte_aus_entries` | Ergänzung 1.2.0 und 1.6.0 (**zwei Fassungen**) | `src/extractors.php` | nein |
| `rest_konto_aus_appdata` | Ergänzung 1.2.0 und 1.6.0 (gleich) | `src/extractors.php` | nein |
| `getKlassen(?int)` | Ergänzung 1.5.0 (`.md`) | `src/WebUntisAuth.php` | nein |
| `getStudents()` | dito | dito | nein |
| `getSchoolyears()` | dito | dito | nein |
| `getCurrentSchoolyear()` | dito | dito | nein |
| `setzeTimeout()`, `post()` | Ergänzung 1.3.0 (`.md`) | — | **ja**, seit 6cd8afd (25.08.) |

**Sechs fehlende Funktionen, nicht eine.** Die Ergänzung 1.3.0 ist
überholt; ihr `post()` ist nicht mehr der Code des Moduls (der baut die
Kopfzeilen inzwischen über `baueKopfzeilen()`).

### Benutzt ein Projekt eine davon?

Gesucht nach **Aufrufen** (`->name(` bzw. `name(` außerhalb von
Deklarationen und Kommentarzeilen), nicht nach Vorkommen.

| Funktion | aufgerufen in | Weg |
|---|---|---|
| `rest_lehrkraefte_aus_entries` | **sprechtag**: `webuntis_adapter.php` (mit `$mitKlausuren`), `sondierung.php`, `messung_sitzung.php` (mit `true`), Tests | Betrieb |
| `rest_konto_aus_appdata` | **sprechtag**: `webuntis_adapter.php` | Betrieb |
| `getKlassen`, `getStudents`, `getSchoolyears`, `getCurrentSchoolyear` | **sprechtag**: nur `backend/api/sondierung.php` (6 Aufrufe) | Diagnose, hinter `sondierung_freigeschaltet` |

**Keine anderen Aufrufer.** Insbesondere:

* **lernzeiten** führt A+ und E+, **ruft aber keine der sechs**: Die
  Stammdatenabfragen laufen über ein eigenes `lz_jsonrpc()`, und
  `backend/auth/extractors.php` wird **von keiner Datei** in `lernzeiten`
  eingebunden (Gegenprobe: dieselbe Suche findet in `sprechtag` acht
  Einbindungen). Die Kopie liegt dort tot.
* **signage** führt A+ und ruft nur `authenticate` (über
  `backend/api/anmeldung.php`).
* **fachkonferenzen** ruft die drei Funktionen der **Moduldatei**
  (`rest_unterricht_aus_entries`, `rest_erster_eintrag`,
  `rest_paare_aus_weekly`) — die in E+ fehlen.

**An der Zusammenführung hängt also genau ein Projekt im Betrieb:
sprechtag.** Und dort hängt es an der **1.6.0**-Signatur.

### Probelauf: lösen die Ergänzungen die roten Tests?

`docs/beilagen/2026-10-12-probelauf.sh`: Moduldatei kopiert, Ergänzung
so angehängt, wie `EINFUEGEN.md` es beschreibt, die beiden roten Dateien
dagegen. Probebaum unter `mktemp -d`, das Repo bleibt unberührt.

| Stand | Funktionen | `extractors_lehrkraefte_test.php` | `extractors_klausuren_test.php` |
|---|---:|---|---|
| **ohne** (Gegenprobe: muss den Fatal zeigen) | 3 | exit 255, Fatal | exit 255, Fatal |
| + Ergänzung **1.2.0** | 5 | exit 1, **32 ok, 4 rot** | exit 1, **9 ok, 5 rot** |
| + Ergänzung **1.6.0** | 5 | exit 0, **36 ok** | exit 0, **14 ok** |

**Die roten Tests sind mit der Ergänzung 1.6.0 vollständig grün.** Mit
1.2.0 bleiben neun Prüfungen rot, alle zum Klausurparameter — die
Testdatei `extractors_lehrkraefte_test.php` wurde in 5eb22f4 auf 1.6.0
erweitert, trägt im Kopf aber noch „v1.2.0".

`docs/CHANGELOG_ergaenzung.md` nennt für die Lehrkraft-Suite **34**
Prüfungen; gemessen sind 36 (Erweiterung in 5eb22f4).

---

## 4 — Der Widerspruch zwischen Doku und Code

### Was jeweils behauptet wird

| Stelle | Weg | Belegstatus laut Stelle selbst |
|---|---|---|
| `docs/README_ergaenzung.md` Z. 131–148, `docs/CHANGELOG_ergaenzung.md` v1.3.0, `src/WebUntisRest_ergaenzung_v1.3.0.md` | `post()` mit **JSON-Body** an `/v1/messages`, Feld `recipients` | **„nicht verifiziert"** — steht dort selbst (CHANGELOG_ergaenzung: *„Die erwartete Feldstruktur wurde nicht verifiziert"*) |
| `src/WebUntisRest.php`, Docblock `postMultipart()` | **multipart**, Teil `request` als JSON, an `/v2/messages/users`, Feld `recipientUserIds` | *„so verschickt die Weboberfläche z. B. Mitteilungen"* — **ohne Datum** |

### Welcher ist gemessen, von wem, wann?

**Der Multipart-Weg ist gemessen**, der JSON-Weg nicht.

* **sprechtag**, `backend/api/mitteilungen.php` Kopfkommentar und
  `mit_varianten()`: *„BELEGT (Mitschnitt der Weboberfläche, 24.07.2026)"*
  — `POST /v2/messages/users`, multipart, `recipientUserIds`. Dort steht
  das Datum, das dem Docblock im Modul fehlt. Der Modulcode ist am
  25.08. aus `sprechtag` zurückgeflossen (CHANGELOG v1.7.0).
* **lernzeiten**, `backend/api/mitteilung.php`: *„DIE FORM DES KOERPERS
  IST GEMESSEN (06.10.2026, ENDPUNKTE.md): multipart, ein Teil
  "request" als JSON"* — aber an **`/v2/messages`**, nicht an
  `/v2/messages/users`.
* **koordination M48** (11.10.), Messung vom 09.10. im Produktivsystem:
  `/v2/messages/users` mit `PARENTS` → **500**; `/v2/messages` mit
  `PARENTS` und Kind-Kennung → **200**, im Elternpostfach angekommen.

Der JSON-Weg an `/v1/messages` ist **nirgends als gesendet belegt**. Die
Doku stammt vom 23.07. — einen Tag **vor** dem Mitschnitt — und wurde
danach nicht berichtigt. Belegt ist für `/v1/messages` nur `GET`
(Posteingang, 200; Sondierung 07/2026).

**Damit ist der Widerspruch nicht zwei-, sondern dreistellig:** Die Doku
nennt `/v1/messages` (JSON), der Modulcode `/v2/messages/users`
(multipart), und die jüngste Messung `/v2/messages` (multipart). Die
beiden letzten sind **kein** Widerspruch untereinander, sondern —
laut M48 — zwei Pfade für zwei Zwecke (Liste vs. Eltern eines Kindes).
Der Docblock im Modul unterscheidet das nicht.

**Keine dieser Messungen trägt eine WebUntis-Version** (wie in `CLAUDE.md`
für alle Messungen vermerkt).

### Folgt ein Projekt dem Doku-Weg?

**Nicht als Hauptweg.** Gesucht nach allen `->post(`- und
`postMultipart(`-Aufrufen außerhalb des Moduls und der Kopien von
`WebUntisRest.php`:

| Projekt | Versand | Pfad |
|---|---|---|
| sprechtag `erinnerungen.php` | `postMultipart` | `/v2/messages/users` |
| sprechtag `messung_sitzung.php` | `postMultipart` | `/v2/messages/users` bzw. Tabelle `MESSUNG_PARENTS_PFADE` |
| sprechtag `mitteilungen.php` | **Kandidatenliste**, siehe unten | |
| lernzeiten `mitteilung.php` | `postMultipart` (genau eine Stelle, von einem eigenen Test gezählt) | `/v2/messages` |

**sprechtag `mitteilungen.php` führt den Doku-Weg als Rückfall:**
`mit_varianten()` probiert der Reihe nach `v2_users_multipart` (belegt),
dann `v2_users_json`, `v1_messages_multipart`, `v1_recipientids`,
**`v1_recipients_ids`** (= genau die Doku-Form) und
`v1_recipients_objekte`, `v2_messages`. Ein Rückfall greift nur, wenn die
belegte Variante mit 400/404/405/415/500 scheitert; bei 401/403/0 bricht
`mit_antwort_bewerten()` ab.

**Die Frage „sendet es möglicherweise gar nicht, und niemand hat es
gemerkt" ist damit für keinen Hauptweg zu bejahen.** Offen bleibt
(Vermutung, nicht geprüft): Scheitert `/v2/messages/users` mit **500** —
was M48 für `PARENTS` gemessen hat —, wandert `mit_senden()` durch die
JSON-Kandidaten an `/v1/messages`. Was dort geschieht, ist nicht belegt.
Entscheiden würde es ein Blick, mit welchen Empfängern `mit_senden()`
heute aufgerufen wird; das liegt in `sprechtag` und außerhalb dieses
Auftrags.

---

## Nebenbei gefundene Fehler (gemeldet, nicht behoben)

1. **`getCurrentSchoolyear()` in A+ (und in der Ergänzung 1.5.0) fängt
   jede `RuntimeException`** und liefert dann `[]` — die Bedeutung
   „Ferien, kein aktives Schuljahr". Der Kommentar sagt *„Fängt den
   Fehler -8998 ab"*. `rpc()` wirft `RuntimeException` aber auch für
   *„WebUntis nicht erreichbar"*, *„kein JSON"* und jeden anderen
   Fehlercode (etwa eine abgelaufene Sitzung). Ein Netzfehler sieht damit
   aus wie die Sommerferien — der Fall aus `FALLSTRICKE.md` Abschnitt 3
   („Der begründende Kommentar daneben macht es schlimmer"). Aufgerufen
   nur in `sprechtag/backend/api/sondierung.php`.
2. **Vermerk und Inhalt von `extractors.php` in lernzeiten/sprechtag
   widersprechen sich** (v1.2.0 vs. Code von 1.6.0) — Abschnitt 2.
3. **Die Kopfkommentare zu `getStudents()` widersprechen sich:** A+
   *„mindestens id, key, name, foreName, longName"*, Ergänzung 1.5.0
   *„id, key, name, foreName, longName, gender … KEINE Klassen- oder
   Jahrgangszuordnung"*. Das ist einer der Widersprüche aus der
   Befundliste vom 11.10. (Abschnitt 4 dort).
4. **A+ verweist auf eine „Ergänzung v1.4.0"**, die es in der Historie
   des Moduls nicht gibt.
5. **`CLAUDE.md` nennt 18 Prüfungen für `run.php`**, es sind 23 — Abschnitt 1.

---

## Einschätzung (keine Entscheidung)

### Lässt sich zusammenführen, ohne dass ein Projekt etwas verliert?

**Für Familie A und die Extraktoren: ja, nach diesem Befund.** Beide
Abweichungen sind reine Erweiterungen des Modulstands:

* **extractors.php:** E0 (3 Funktionen) und E+ (2 Funktionen) haben
  keinen gemeinsamen Namen. Die Vereinigung — Moduldatei plus Ergänzung
  **1.6.0** — verliert nichts, und der Probelauf zeigt beide roten
  Suiten damit grün (36 + 14). **Wer 1.2.0 nimmt, bricht `sprechtag`**
  (zweiter Parameter) und lässt neun Prüfungen rot.
* **WebUntisAuth A:** A0 ist echte Teilmenge von A+; alle gemeinsamen
  Methoden sind codegleich, bis auf das ID-Präfix in `rpc()`.
* **WebUntisRest:** nichts zu tun.

**Was im Weg steht:**

1. **`getStudents()` ist ein personenbezogener Endpunkt.** Ins Modul
   übernommen, landete er mit der nächsten Kopie auch in
   `fachkonferenzen` und `signage`, die ihn nicht brauchen —
   `REIHENREGELN.md` Abschnitt 9 („Personenbezogene Endpunkte gehören
   nicht in eine Anwendung, die ohne sie auskommt"). Heute ruft ihn nur
   die Sondierung von `sprechtag`. Die Zusammenführung muss das
   entscheiden; Zusammenführen heißt hier nicht automatisch „alle vier".
2. **Der Fehler in `getCurrentSchoolyear()`** würde mit übernommen.
3. **Familie B ist kein Teil dieser Zusammenführung.** Sie lebt in
   `webuntis-auth-php`, hat dort drei verschiedene `authenticate`-Rümpfe
   und denselben globalen Klassennamen — die Doppelung aus `CLAUDE.md`
   („zwei `require` im selben Request sind ein Fatal") bleibt bestehen,
   auch wenn A vereinheitlicht ist.

### Braucht die Zusammenführung eine Modulversion, die verteilt wird?

**Die Behebung der roten Tests bleibt innerhalb des Moduls** — sie
braucht nur `src/extractors.php` mit den beiden Funktionen. Kein Projekt
muss dafür etwas übernehmen.

**Die fünf `fassung-abweichend`-Funde des Bestandslaufs verschwinden aber
erst mit einer Verteilung**, und die verlangt eine Versionsnummer —
die Vermerke in den Projekten nennen heute keine (A+, fachkonferenzen
E0) oder eine falsche (E+). Nach `REIHENREGELN.md` Abschnitt 6 wird eine
vergebene Nummer getaggt; **dieses Repo hat noch keinen einzigen Tag**,
und die bisherigen Nummern 1.0.0–1.7.0 stehen nur in Commit-Titeln, die
nicht durchgängig zum Inhalt passen. Die erste getaggte Version wäre
deshalb ein eigener Schritt vor der Verteilung.

Nicht dringend ist die Verteilung für `lernzeiten` (die Kopie von E+ wird
nicht eingebunden) und `signage` (nutzt keine Zusatzmethode). Für
`sprechtag` ist sie verlustfrei, sofern die Vereinigung die 1.6.0-Fassung
trägt.

---

## Hat eine Regel in diesem Lauf etwas verhindert?

**Ja, dreimal.**

1. **„Was geschrieben ist, ist nicht, was angekommen ist" / „Ein Beleg,
   dessen Werkzeug fehlt, ist nicht wiederholbar."** Der erste Probelauf
   lief als eingefügter Block in der zsh und meldete für die
   1.2.0-Variante `exit=0` — **bei fünf roten Prüfungen**. Ein einzelner
   Aufruf derselben Datei ergab `exit=1`. Die Ursache wurde nicht
   gesucht; der Lauf wurde in ein `#!/bin/bash`-Skript verlegt, das den
   Exit-Code in eine Variable nimmt, und gab dann `exit=1`. Ohne den
   Widerspruch zwischen `exit=0` und `rot=5` in derselben Zeile wäre
   „1.2.0 genügt, Tests grün" als Befund durchgegangen. (`REIHENREGELN.md`
   Abschnitt 3: „Ein Block zum Einfügen ist ein Skript ohne Kopfzeile.")
2. **„Eine Prüfung ohne ihre Voraussetzung gilt nicht als bestanden."**
   Der Probebaum hat deshalb eine Variante `ohne`, die den Fatal
   reproduzieren **muss**. Erst damit ist belegt, dass der Probebaum den
   Repo-Zustand nachbildet und das Grün der 1.6.0-Variante von der
   Ergänzung kommt.
3. **„Zu prüfen, nicht zu übernehmen."** Die Zahl 18 aus `CLAUDE.md` ist
   veraltet (23), und „fünf Fassungen" bezeichnet in `CLAUDE.md` und in
   der Erhebung vom 09.09. zwei verschiedene Zählungen.

**Ein Werkzeugbefund dazu:** `grep` ist in der Shell dieser Sitzung eine
Funktion, die `ugrep` aufruft. Ein Aufruf mit `--` vor dem Muster machte
aus den folgenden `--exclude-dir=…` Dateinamen (*„ugrep: warning:
--exclude-dir=.git: No such file or directory"*). Diesmal laut, weil die
Warnung durchkam — mit `2>/dev/null` wäre die Suche ohne Ausschluss
gelaufen. Betroffen war nur die Suche nach `->post(` in Abschnitt 4;
sie lief dadurch **ohne** Ausschluss, also über mehr Dateien, nicht über
weniger — ihre Treffer liegen sämtlich außerhalb der auszuschließenden
Verzeichnisse und gelten.

---

## Was nicht geklärt wurde

* **Ob `mit_senden()` in sprechtag im Betrieb je über einen JSON-Rückfall
  an `/v1/messages` gegangen ist.** Das stünde im Protokoll der
  Mitteilungen (`versuche`), nicht im Code.
* **Was in der uncommitteten Änderung an `sprechtag/docs/MITTEILUNGEN.md`
  steht.** Nicht gelesen, weil fremde laufende Arbeit.
* **Die drei übrigen offenen Punkte aus M48** (Token übernehmen,
  Antwortkopfzeilen, Scope-Kommentar) gehören nicht zu den Abschnitten
  1–4 dieses Auftrags und wurden nicht erhoben.
