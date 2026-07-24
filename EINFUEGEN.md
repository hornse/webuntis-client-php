# Änderungen am webuntis-client-php – Übersicht

Alle Änderungen, die aus dem Projekt `sprechtag` ins Modul-Repository
zurückfließen sollen. Stand: 24.07.2026, Zielversion **1.5.0**.

## Auf einen Blick

| Datei im Repo | Änderung | Version | Quelle in diesem Paket |
|---|---|---|---|
| `src/extractors.php` | 2 Funktionen **anhängen** | 1.2.0 | `src/extractors_ergaenzung_v1.2.0.php` |
| `src/WebUntisRest.php` | 1 Eigenschaft, 2 Methoden, 1 Zeile ändern | 1.3.0 | `src/WebUntisRest_ergaenzung_v1.3.0.md` |
| `src/WebUntisAuth.php` | 4 Methoden ergänzen | 1.4.0 / 1.5.0 | `src/WebUntisAuth_ergaenzung_v1.5.0.md` |
| `tests/extractors_lehrkraefte_test.php` | **neu** | 1.2.0 | `tests/extractors_lehrkraefte_test.php` |
| `README.md` | Abschnitte ergänzen | – | `docs/README_ergaenzung.md` |
| `CHANGELOG.md` | 4 Einträge ergänzen | – | `docs/CHANGELOG_ergaenzung.md` |

Alle Änderungen sind **additiv** – bestehende Signaturen bleiben gültig.
Einzige Ausnahme: `getKlassen()` bekommt einen optionalen Parameter, was
vorhandene Aufrufe nicht bricht.

## Schritt für Schritt

### 1. `src/extractors.php` (→ v1.2.0)

Aus `src/extractors_ergaenzung_v1.2.0.php` **alles ab der ersten
`/**`-Zeile** ans Ende anhängen; den `<?php`-Kopf nicht mitkopieren.

Neu: `rest_lehrkraefte_aus_entries()`, `rest_konto_aus_appdata()`.

### 2. `src/WebUntisRest.php` (→ v1.3.0)

Siehe `src/WebUntisRest_ergaenzung_v1.3.0.md`. Drei Eingriffe:
Eigenschaft `$timeout`, eine geänderte Zeile in `rohGet()`, dazu die
Methoden `setzeTimeout()` und `post()`.

### 3. `src/WebUntisAuth.php` (→ v1.4.0 und v1.5.0)

Siehe `src/WebUntisAuth_ergaenzung_v1.5.0.md`. Vier Methoden hinter
`getRooms()` einfügen: `getKlassen()` (mit optionaler `schoolyearId`),
`getStudents()`, `getSchoolyears()`, `getCurrentSchoolyear()`.

**Wichtig:** Die Datei enthält die aktuelle Fassung von `getKlassen()`
**mit** Parameter. Eine ältere Lieferung ohne Parameter ist überholt –
ohne ihn scheitert der Aufruf in den Sommerferien.

### 4. Tests

`tests/extractors_lehrkraefte_test.php` übernehmen und prüfen:

```bash
php tests/extractors_lehrkraefte_test.php   # Exit-Code 0 = grün
```

34 Prüfungen, ohne Netz und Datenbank lauffähig.

### 5. Dokumentation

`docs/README_ergaenzung.md` und `docs/CHANGELOG_ergaenzung.md` enthalten
fertige Textblöcke. Die README-Ergänzung dokumentiert auch die bekannten
Endpunkte, die personType-Werte und den Ferien-Fallstrick.

### 6. Version und Freigabe

```bash
# composer.json bzw. Versionskonstante auf 1.5.0
git add -A
git commit -m "v1.5.0: Schuljahre, Klassen/Schueler, POST, Lehrkraft-Extraktor"
git tag v1.5.0
git push && git push --tags
```

## Rückwirkung auf sprechtag

Im Projekt liegen alle Ergänzungen bereits vendored vor
(`backend/auth/`). Nach dem Einspielen sind Modul und Projekt wieder
synchron; künftige Änderungen wie gewohnt zuerst im Modul, dann kopieren.
