# Änderungen am webuntis-client-php – Übersicht

Alle Änderungen, die aus dem Projekt `sprechtag` ins Modul-Repository
zurückfließen sollen. Zwei Versionssprünge, vier Dateien.

## Auf einen Blick

| Datei im Repo | Änderung | Version | Quelle in diesem Paket |
|---|---|---|---|
| `src/extractors.php` | 2 Funktionen **anhängen** | 1.2.0 | `src/extractors_ergaenzung_v1.2.0.php` |
| `src/WebUntisRest.php` | 1 Eigenschaft, 2 Methoden, 1 Zeile ändern | 1.3.0 | `src/WebUntisRest_ergaenzung_v1.3.0.md` |
| `tests/extractors_lehrkraefte_test.php` | **neu** | 1.2.0 | `tests/extractors_lehrkraefte_test.php` |
| `README.md` | Abschnitte ergänzen | – | `docs/README_ergaenzung.md` |
| `CHANGELOG.md` | 2 Einträge ergänzen | – | `docs/CHANGELOG_ergaenzung.md` |

## Schritt für Schritt

### 1. `src/extractors.php` (→ v1.2.0)

Aus `src/extractors_ergaenzung_v1.2.0.php` **alles ab der ersten
`/**`-Zeile** ans Ende von `extractors.php` anhängen. Den `<?php`-Kopf
nicht mitkopieren.

Neue Funktionen: `rest_lehrkraefte_aus_entries()`, `rest_konto_aus_appdata()`.
Bestehende Funktionen bleiben unverändert – rein additive Änderung.

### 2. `src/WebUntisRest.php` (→ v1.3.0)

Drei kleine Eingriffe, im Detail beschrieben in
`src/WebUntisRest_ergaenzung_v1.3.0.md`:

1. Eigenschaft `private int $timeout = 25;` bei den übrigen Eigenschaften
   ergänzen
2. In `rohGet()`: `CURLOPT_TIMEOUT => 25` durch
   `CURLOPT_TIMEOUT => $this->timeout` ersetzen
3. Methoden `setzeTimeout()` und `post()` einfügen

### 3. Tests

`tests/extractors_lehrkraefte_test.php` unverändert übernehmen. Prüfen mit:

```bash
php tests/extractors_lehrkraefte_test.php   # Exit-Code 0 = grün
```

Die Tests brauchen weder Netz noch Datenbank.

### 4. Dokumentation

`docs/README_ergaenzung.md` und `docs/CHANGELOG_ergaenzung.md` enthalten
fertige Textblöcke zum Einfügen. Die README-Ergänzung umfasst auch eine
Tabelle der bekannten Endpunkte und der personType-Werte – Wissen, das
bisher nur verstreut in den Projekten stand.

### 5. Version und Freigabe

```bash
# composer.json bzw. Versionskonstante auf 1.3.0
git add -A
git commit -m "v1.3.0: rest_lehrkraefte_aus_entries, rest_konto_aus_appdata, post(), setzeTimeout()"
git tag v1.3.0
git push && git push --tags
```

## Rückwirkung auf sprechtag

Im Projekt liegen beide Ergänzungen bereits vendored vor
(`backend/auth/extractors.php`, `backend/auth/WebUntisRest.php`) und sind
dort identisch. Nach dem Einspielen ins Modul sind Projekt und Repo wieder
synchron; künftige Änderungen wie gewohnt zuerst im Modul, dann kopieren.
