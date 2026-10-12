# Bericht: Das Client-Modul zusammengeführt — v2.0.0

Ergebnis von `hornse/koordination`, `docs/AUFTRAG-webuntis-client-zusammenfuehren.md`
(Commit 1b5577d, 12.10.2026). **Gelaufen am 12.10.2026.**

Kein Vendoring, kein Push, nichts an den Kopien in den Projekten, nichts
an der zweiten Modulfamilie (`webuntis-auth-php`).

---

## Vorher: die Rückfrage

Der Auftrag nennt eine uncommittete Änderung an `docs/MITTEILUNGEN.md` in
einem Projekt (`sprechtag`). **Beim Beginn war sie nicht mehr offen:**
`sprechtag` war sauber, die Datei als `d8cac3a` (12.10., 09:07,
„Doku-Umbau Schritt 3") committet; danach nur `defba72` und `308c09a`,
beide Doku. Die committete Fassung nennt multipart — an die Eltern eines
Kindes über `/v2/messages` mit `recipientOption: PARENTS`, an ein Konto
über `/v2/messages/users` — und deckt sich mit Abschnitt 3 des Auftrags.
Am Code gelesen: `mit_senden_eltern()` ist ein einziger Aufruf ohne
Rückfallvarianten. Offen bleibt dort, eigenständig, die Aufräumliste
Punkt 9 (leeres `catch` in `mit_versand_ausfuehren()`) und Punkt 6.

**Eine Rückfrage während des Laufs:** die Versionsnummer, siehe unten.

---

## 1 — Ausgangsstand: Stimmten die Zahlen aus dem Bestandsbefund?

**Ja, alle.**

* `git status`: Modul sauber (7f4df48). Projekte sauber bis auf
  `projektstunden` (`?? sql/gen/__pycache__/`, wie im Befund);
  `lernzeiten` ist seitdem sauber.
* Tests einzeln: `run.php` 23, `webuntis_rest_test.php` 8, die beiden
  `extractors_*` Exit 255 mit Fatal — wie im Befund.
* Datei-Prüfsummen aller Kopien (`WebUntisAuth.php`, `WebUntisRest.php`,
  `extractors.php` in sieben Projekten und zwei Quellen): **alle gleich
  dem Befund**, keine Datei hat sich seit dem Morgen geändert.

---

## 2 — Welche Funktionen hängen an der Stammdatenabfrage?

**Im Modul: keine.** `getStudents()` ist in der Anleitung ein Einzeiler
(`return $this->rpc('getStudents')`); keine der übrigen fünf Funktionen
ruft sie, und sie ruft keine. `getKlassen`, `getSchoolyears`,
`getCurrentSchoolyear` und die beiden Extraktoren sind ohne sie
vollständig. **Die Zusammenführung trägt ohne sie** — Beleg: alle
111 Prüfungen grün, darunter die 50 der beiden vorher roten Suiten.

**In den Projekten: eine Anwendung, eine Datei.** Aufrufer von
`->getStudents(` gibt es nur in `sprechtag/backend/api/sondierung.php`
(zwei Stellen, Zeilen 255 und 323), hinter dem Schalter
`sondierung_freigeschaltet`. Nichts bricht **jetzt** — die Kopien sind
unverändert. Was eine Verteilung dort ändern würde, steht in Abschnitt 6.

Nicht angehalten, weil der Auftrag „anhalten, wenn etwas bricht" an die
Zusammenführung knüpft und im Modul nichts bricht. **Das ist eine
Auslegung**; die Folge für die Verteilung ist unten benannt.

---

## 3 — Prüfungszahl

**Erwartung, genannt nach Festlegung der Prüfungen und vor dem
Schreiben des Codes: 31 → 111. Gemessen: 111.**

| Datei | vorher | nachher | was neu ist |
|---|---:|---:|---|
| `tests/run.php` | 23 | 31 | kein Funktionsname doppelt in `src/` (1); keine Schülerabfrage im Code, mit zwei eingebauten Gegenproben (Zeichenkette wird gefunden, Kommentar nicht) (3); widerlegte Scope-Deutung weg, `messages/permissions` genannt (2); README nennt den gemessenen Versandweg mit Datum, das JSON-Beispiel in der Ergänzungsdoku steht als ÜBERHOLT gekennzeichnet (2) |
| `tests/webuntis_rest_test.php` | 8 | 8 | — |
| `tests/extractors_lehrkraefte_test.php` | Fatal | 36 | Zusammenführung |
| `tests/extractors_klausuren_test.php` | Fatal | 14 | Zusammenführung |
| `tests/webuntis_auth_test.php` | — | 12 | neu: Probeserver (1), `getCurrentSchoolyear` in sechs Fällen, `getSchoolyears` (2), `getKlassen` (2), keine `getStudents` (1) |
| `tests/sammelskript_test.php` | — | 10 | neu: das Sammelskript |

**Die rote Meldung vor der Behebung ist belegt:** Nach dem Schreiben der
Prüfungen und vor dem Code meldete `bash tests/alle.sh`: 4 von 6 Dateien
rot, 45 bestanden, 18 rot — darunter alle sechs neuen Prüfungen in
`run.php`, die nicht schon an der Abwesenheit von `getStudents` hingen,
und zehn der zwölf in `webuntis_auth_test.php`.

**Dabei fiel ein Fehler in einer meiner Gegenproben auf:** Die
eingebaute Gegenprobe „der Aufruf als Zeichenkette wird gefunden"
erwartete Zeile 3, der Aufruf stand in Zeile 2. Berichtigt, bevor Code
geschrieben wurde.

### Die Eigenschaft wird ausgeführt, nicht der Quelltext gelesen

`getCurrentSchoolyear()` wird gegen einen lokalen PHP-Server geprüft, den
die Suite selbst startet (`php -S`), über das echte `rpc()` mit cURL:

| Fall | Ergebnis |
|---|---|
| Fehler -8998 | `[]` |
| Ergebnis `null` | `[]` |
| ein Schuljahr | durchgereicht |
| Fehler -8520 | **Ausnahme**, nicht `[]` |
| Antwort ohne JSON | **Ausnahme** |
| Port ohne Server | **Ausnahme** „nicht erreichbar" |

Der Wortlaut von -8998 ist belegt (CHANGELOG_ergaenzung v1.5.0); die
übrigen Antworten des Probeservers sind **erfunden** und in der Datei so
benannt. **Nicht gemessen** ist, ob `getCurrentSchoolyear` selbst
zwischen zwei Schuljahren -8998 oder `null` liefert — beides ergibt `[]`,
und der Kommentar sagt das.

### Mutationsserie

`docs/beilagen/2026-10-12-mutationen.sh` — 16 Mutationen, je mit
Wirkungsnachweis (Muster vorher vorhanden, neue Form nachher), Rücknahme
aus einer Sicherungskopie und Prüfsummenvergleich. Stand vor und nach der
Serie gleich (`f491891d233c8aab`; der letzte Durchgang nach den
Doku-Änderungen: `9c801d56ee1aaa2b`, ebenfalls 16 von 16).

**Erster Durchgang: 15 von 16.** M10 (`alle.sh` endet bei Rot mit
Exit 0) lief durch. Bevor das erklärt wurde, wurde es nachgestellt:
`sammelskript_test.php` meldet in diesem Zustand drei FEHLER, die
Zusammenfassung von `alle.sh` sagt „ROT: 1 von 6" — aber das Gerüst nahm
den Exit-Code **vom mutierten `alle.sh` selbst**. Der Prüfling war sein
eigener Richter. Das Gerüst nimmt den Exit-Code jetzt aus den direkten
Läufen der Testdateien. **Zweiter Durchgang: 16 von 16.**

Was daraus für den Betrieb folgt: Ein `alle.sh`, das bei Rot mit 0
endet, verrät sich in seiner eigenen Zusammenfassung und in
`sammelskript_test.php` — **nicht** an seinem Exit-Code. Wer nur den
Exit-Code liest, sieht es nicht.

Trennschärfe: M1 (catch fängt wieder alles) lässt den Fall -8998 grün;
M2 (catch fängt -8998 nicht mehr) lässt die drei „wird NICHT zu []"
grün; M4 (getStudents als Zeichenkette in einer privaten Methode
anderen Namens) trifft nur die Tokenprüfung in `run.php`, nicht die
Methodenprüfung — die beiden Stufen melden sich mit verschiedenem
Wortlaut. Und die echte `src/WebUntisAuth.php` nennt `getStudents()` in
einem Kommentar; die Prüfung bleibt dort grün.

**Nur durch Gegenproben belegt** sind die Doku-Prüfungen (5d): Sie
zeigen, dass die Kennzeichnung dasteht, nicht, dass der Text richtig ist.

---

## 4 — Der Wortlaut der berichtigten Doku-Stelle

In `docs/README_ergaenzung.md`, direkt unter der Überschrift
`### post(string $pfad, array $daten): array *(ab v1.3.0)*`, vor dem
unveränderten alten Text:

> **ÜBERHOLT – der Versandweg unten ist widerlegt (v2.0.0, 12.10.2026).**
> Das Beispiel schickt eine Mitteilung als JSON-Body an `/v1/messages`.
> Dieser Weg ist **nirgends als gesendet belegt**; er wurde am 23.07.2026
> aufgeschrieben, einen Tag **vor** dem Mitschnitt, der ihn widerlegt,
> und der Changelog nannte ihn selbst „nicht verifiziert". **Gemessen
> ist multipart** – ein Teil `request` mit dem JSON:
>
> | Datum | Pfad | Beleg |
> |---|---|---|
> | 24.07.2026 | `/v2/messages/users`, `recipientUserIds` | Mitschnitt der Oberfläche, `sprechtag` |
> | 06.10.2026 | `/v2/messages` | Messung `lernzeiten` |
> | 09.10.2026 | `/v2/messages` mit `recipientOption: PARENTS` → 200, angekommen; `/v2/messages/users` damit → 500 | `koordination` M48 |
>
> Gültig ist `README.md`, Abschnitt „Mitteilungen versenden". `post()`
> selbst bleibt richtig – für Endpunkte, die JSON erwarten, etwa die
> Empfängersuche. Der alte Text folgt unverändert.

Die gültige Fassung steht in `README.md`, Abschnitt „Mitteilungen
versenden", mit derselben Tabelle in Aufrufform. Dazu gekennzeichnet:
`docs/CHANGELOG_ergaenzung.md` (v1.3.0-Hinweis, die zwei v1.4.0-Einträge
mit verschiedenen Feldbeschreibungen von `getStudents`),
`docs/WebUntisAuth_ergaenzung_v1.5.0.md`,
`docs/WebUntisRest_ergaenzung_v1.3.0.md`, `EINFUEGEN.md`.

**Nebenbei berichtigt**, weil es die zusammengeführte Funktion betrifft:
`README_ergaenzung.md` nannte als Filterregel „schließt … `EXAM` aus" —
seit dem Parameter `$mitKlausuren` gilt das nur noch mit `false`.

---

## 5 — Die Version, und ob der Tag richtig sitzt

**Rückfrage gestellt, bevor getaggt wurde.** Der Auftrag legt v1.0.0
fest. Die Nummer ist im Repo vergeben: Commit 9018d5c heißt „v1.0.0",
`CHANGELOG.md` führt „## v1.0.0 (Juli 2026)" für den Stand vom 17.07.
Und `koordination/bin/bestand.sh` (gelesen, nicht gelaufen) meldet
`versionsrueckfall`, sobald ein Commit-Titel „v1.0.0: …" nach
„v1.7.0: …" kommt. **Entschieden: v2.0.0** — frei, höher als jede je
genannte Nummer, und der Sache nach richtig: Gegenüber den Kopien in den
Projekten ist der Stand nicht rückwärtsverträglich (`getStudents()`
fehlt, `getCurrentSchoolyear()` wirft, wo es `[]` lieferte).

**Abweichung vom Auftrag, durch Rückfrage gedeckt.** Der Befund dazu
gehört nach `koordination`: Der Auftrag hat die Nummer festgelegt, ohne
den Bestand anzusehen (REIHENREGELN 6, „Ist sie frei?").

**Zu erwarten im nächsten Bestandslauf** (gelesen, nicht gelaufen): Mit
dem ersten Tag wird `webuntis-client-php/version-ohne-tag/verlauf`
erscheinen — die Commit-Titel nennen v1.3.0, v1.5.0, v1.6.0, v1.7.0 ohne
Tag. Das ist richtig so und kein Fehler des Laufs; der Changelog
`[2.0.0]` erklärt diese Nummern. Nachträgliche Tags darauf wären
Behauptungen über Stände, deren Code die Nummer nicht trug.

Ob der Tag auf dem richtigen Commit sitzt: siehe den Nachtrag am Ende,
geschrieben nach dem Taggen.

---

## 6 — Was eine Verteilung in den Projekten ändern würde

Gelesen an den Kopien und ihren Aufrufern, **nicht ausgeführt.**

| Projekt | Datei | heute | nach Verteilung von v2.0.0 | Wirkung im Projekt |
|---|---|---|---|---|
| fachkonferenzen | `backend/auth/WebUntisAuth.php` | A0, kein Vermerk | + `getKlassen`, `getSchoolyears`, `getCurrentSchoolyear`; Vermerk v2.0.0 | keine — ruft keine davon |
| fachkonferenzen | `backend/auth/extractors.php` | E0, Vermerk ohne Version | + `rest_lehrkraefte_aus_entries`, `rest_konto_aus_appdata` | keine — ruft die drei alten |
| fachkonferenzen | `backend/auth/WebUntisRest.php` | v1.7.0 | nur Kommentare (`jwtDaten`, `postMultipart`) | keine |
| kurse | `backend/auth/WebUntisRest.php` | v1.7.0 | nur Kommentare | keine |
| lernzeiten | `backend/auth/WebUntisAuth.php` | A+ | **− `getStudents`**; `getCurrentSchoolyear` wirft bei anderen Fehlern; ID-Präfix `spr_` → `fako_` | keine — lernzeiten ruft keine der vier (eigene `lz_jsonrpc()`) |
| lernzeiten | `backend/auth/extractors.php` | E+, Vermerk „v1.2.0" | + die drei Funktionen von E0; Vermerk v2.0.0 | keine — die Datei wird in lernzeiten **nirgends eingebunden** |
| lernzeiten | `backend/auth/WebUntisRest.php` | v1.7.0 | nur Kommentare | keine |
| signage | `backend/auth/WebUntisAuth.php` | A+ | wie lernzeiten | keine — ruft nur `authenticate` |
| sprechtag | `backend/auth/WebUntisAuth.php` | A+ | wie lernzeiten | **zwei Stellen, siehe unten** |
| sprechtag | `backend/auth/extractors.php` | E+, „v1.2.0" | + die drei Funktionen von E0 | keine — die beiden benutzten sind codegleich |
| sprechtag | `backend/auth/WebUntisRest.php` | v1.7.0 | nur Kommentare | keine |

**Nicht betroffen:** `projektstunden`, `schulprozesse` (Familie B, kein
Client-Modul); `kurse/backend/auth/WebUntisAuth.php` (Familie B).

### sprechtag: was die Verteilung dort ändert

Alle Stellen in `backend/api/sondierung.php`, hinter
`sondierung_freigeschaltet` (Vorlage: `false`):

1. **Zeile 149, `$wu->getCurrentSchoolyear()` — ohne `try`.** Heute
   meldet ein Netzfehler oder eine abgelaufene Sitzung dort „Kein aktives
   Schuljahr". Nach der Verteilung fliegt die Ausnahme aus der
   Sondierungsfunktion. **Das ist die beabsichtigte Berichtigung**, aber
   eine Verhaltensänderung: Was der Aufrufer dann zeigt, ist nicht
   gelesen.
2. **Zeile 255, `$wu->getStudents()` in `catch (Throwable)` mit
   Kommentar `/* unten als „offen" ausgewiesen */`.** Nach der
   Verteilung wirft der Aufruf einen `Error` (Methode fehlt), das `catch`
   fängt ihn, und der Bericht sagt „getStudents nicht abrufbar –
   Kennungsvergleich offen". **Das ist der Fall aus FALLSTRICKE 3**: ein
   Programmierfehler, der wie ein Betriebsfehler aussieht.
3. **Zeile 323, `$wu->getStudents()` in `catch (Throwable)`.** Der
   Bericht zeigt dann `fehler: Call to undefined method …` — sichtbar,
   aber als Sondierungsergebnis verkleidet.

**Also: Die Verteilung nach sprechtag braucht dort eine Entscheidung**,
bevor die Kopie ersetzt wird — die Schülerabfrage in die Anwendung holen
(projekteigen, mit eigenem `rpc`-Weg) oder die beiden Abschnitte der
Sondierung streichen. Das gehört in den Verteilungsauftrag, nicht hierher.

---

## 7 — Hat eine Regel in diesem Lauf etwas verhindert?

**Ja, viermal.**

1. **„Vor dem Vergeben einer Versionsnummer den Bestand ansehen: Ist sie
   frei?"** (REIHENREGELN 6). Ohne den Blick in `CHANGELOG.md` und
   `bin/bestand.sh` hätte „v1.0.0" zwei Stände benannt und einen
   `versionsrueckfall`-Befund ausgelöst — oder dazu verleitet, den
   Commit-Titel um den Befund herumzuformulieren.
2. **„Die Gegenprobe selbst wird geprüft"** und **„Bleibt eine Behebung
   ohne sichtbare Wirkung, suche eine zweite Ursache"** — hier
   umgekehrt: Bei M10 lag die Ursache nicht in der Prüfung, sondern im
   Gerüst. Erst nachgestellt, dann erklärt, dann geändert. Ohne die
   Nachstellung wäre „sammelskript_test fängt das nicht" in den Bericht
   gegangen — falsch.
3. **„Wo aus einem Vorfall bekannt ist, wie die falsche Fassung aussieht,
   sucht die Prüfung ausdrücklich nach ihr."** Die Prüfung 5c sucht den
   Satz „bedeutet nur LESEN", nicht nur die Anwesenheit von
   `messages/permissions` — M12 setzt den alten Satz **neben** den neuen
   und wird nur so gefangen.
4. **„Ein Eintrag, der eine Datei benennt, wird nicht ohne sie
   committet."** `CLAUDE.md` verweist auf diesen Bericht; er geht im
   selben Commit hinein.

**Zweimal hat die zsh-Falle „führendes `=`" einen Befehl abgebrochen**
(`echo ======` als Trennlinie in einem eingefügten Block). Ohne Schaden,
weil sie abbricht statt zu lügen; danach mit `---` getrennt.

---

## 8 — Nebenbei gefunden, nicht behoben

* **`curl_close()` ist seit PHP 8.5 als veraltet markiert** (lokal 8.5.8,
  `Deprecated` bei jedem Aufruf in `WebUntisAuth::rpc()` und den
  Aufrufen von `WebUntisRest`). Ohne Wirkung seit PHP 8.0. Welche
  Fassung die Server führen, ist nicht geprüft.
* **`getStudents()`-Feldbeschreibungen**: drei Fassungen in diesem Repo
  (zwei in `CHANGELOG_ergaenzung`, eine in der Anleitung 1.5.0), keine
  geprüft. Gekennzeichnet, nicht entschieden — das Modul führt die
  Methode nicht mehr.

---

## Nachtrag nach dem Taggen

*(wird nach `git tag` ergänzt)*
