# WebUntisAuth – Ergänzungen v1.4.0 und v1.5.0

Einzufügen in `src/WebUntisAuth.php` (Klasse `WebUntisAuth`), direkt
hinter `getRooms()`.

> Anleitung, kein lauffähiges PHP – die Blöcke sind Klassen-Member.

> **ÜBERHOLT seit v2.0.0 (12.10.2026).** Bis dahin lag diese Datei in
> `src/`. Übernommen sind `getKlassen()`, `getSchoolyears()` und
> `getCurrentSchoolyear()` – letztere **berichtigt**: Der `catch` unten
> fängt jede `RuntimeException`, obwohl der Kommentar nur -8998 nennt;
> damit sah ein Netzfehler aus wie „kein Schuljahr aktiv". **Nicht
> übernommen ist `getStudents()`** (personenbezogen, REIHENREGELN 9).
> **„v1.4.0" gibt es in der Historie dieses Repos nicht** – kein Commit,
> keine Datei; die Methoden kamen erst mit dieser Anleitung (2b6838a,
> 24.07.2026) herein. Die Feldbeschreibung von `getStudents()` unten
> widerspricht der in `docs/CHANGELOG_ergaenzung.md` (zweiter
> v1.4.0-Eintrag) – keine der beiden ist hier geprüft.

---

## v1.4.0 – Klassen und Schüler:innen

```php
    /**
     * Alle Klassen (Ergänzung v1.4.0, Parameter ab v1.5.0).
     *
     * WICHTIG: Ohne Parameter bezieht sich der Aufruf auf das AKTUELLE
     * Schuljahr. Zwischen zwei Schuljahren (Sommerferien) gibt es keines –
     * WebUntis antwortet dann mit Fehler -8998 ("schoolyear is null").
     * In dem Fall eine schoolyearId aus getSchoolyears() übergeben.
     */
    public function getKlassen(?int $schoolyearId = null): array
    {
        return $this->rpc('getKlassen',
            $schoolyearId === null ? [] : ['schoolyearId' => $schoolyearId]);
    }

    /**
     * Alle Schüler:innen (Ergänzung v1.4.0).
     * Liefert je Eintrag id, key, name, foreName, longName, gender.
     *
     * ACHTUNG: personenbezogene Daten. Befund 07/2026: KEINE Klassen-
     * oder Jahrgangszuordnung enthalten – wer die braucht, muss sie über
     * Stundenpläne oder getKlassen() ermitteln.
     */
    public function getStudents(): array { return $this->rpc('getStudents'); }
```

---

## v1.5.0 – Schuljahre

Notwendig, weil zwischen zwei Schuljahren kein aktives Schuljahr existiert
und alle darauf bezogenen Aufrufe scheitern.

```php
    /** Alle Schuljahre mit id, name, startDate, endDate (Ergänzung v1.5.0). */
    public function getSchoolyears(): array { return $this->rpc('getSchoolYears'); }

    /**
     * Aktuelles Schuljahr oder [] in den Ferien (Ergänzung v1.5.0).
     * Fängt den Fehler -8998 ab und liefert dann ein leeres Array.
     */
    public function getCurrentSchoolyear(): array
    {
        try {
            $r = $this->rpc('getCurrentSchoolyear');
            return is_array($r) ? $r : [];
        } catch (RuntimeException $e) {
            return [];
        }
    }
```

---

## Anwendungsbeispiel

```php
$wu->authenticate($benutzer, $passwort);

$aktuell = $wu->getCurrentSchoolyear();
if ($aktuell === []) {
    // Ferien: jüngstes Schuljahr aus der Liste nehmen
    $jahre = $wu->getSchoolyears();
    $ids = array_map(fn($s) => (int)$s['id'], $jahre);
    $klassen = $wu->getKlassen(max($ids));
} else {
    $klassen = $wu->getKlassen();
}
```

## Nach dem Einfügen

```bash
php -l src/WebUntisAuth.php
```

Version auf 1.5.0 setzen, CHANGELOG ergänzen.
