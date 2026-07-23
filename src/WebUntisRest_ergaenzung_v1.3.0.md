# WebUntisRest – Ergänzungen v1.3.0

Einzufügen in `src/WebUntisRest.php` (Klasse `WebUntisRest`).
Drei Eingriffe, alle unten im Volltext.

> Diese Datei ist eine Anleitung, kein lauffähiges PHP – die Code-Blöcke
> sind Klassen-Member und gehören in die bestehende Klasse.

---

## (A) Timeout konfigurierbar machen

**Grund:** Diagnose- und Sondierläufe machen viele Aufrufe hintereinander.
Mit dem festen Timeout von 25 s läuft ein Durchgang leicht in das
Proxy-Limit des Webservers (Uberspace kappt bei ca. 60 s). Mit
`setzeTimeout(8)` bleibt ein kompletter Sondierlauf darunter.

### Schritt 1 – Eigenschaft ergänzen

Bei den übrigen Eigenschaften (unter `$tenantId`):

```php
    private int $timeout = 25;
```

### Schritt 2 – eine Zeile in `rohGet()` ändern

```php
//  ALT:
        CURLOPT_TIMEOUT        => 25,

//  NEU:
        CURLOPT_TIMEOUT        => $this->timeout,
```

### Schritt 3 – Methode einfügen

Zum Beispiel hinter `mitSessionCookie()`:

```php
    /**
     * Timeout je Aufruf in Sekunden (Standard 25).
     * Für viele Aufrufe in Folge niedriger setzen, damit der
     * Gesamtdurchlauf unter dem Proxy-Limit des Webservers bleibt.
     */
    public function setzeTimeout(int $sekunden): void
    {
        $this->timeout = max(1, $sekunden);
    }
```

---

## (B) Schreibender Zugriff: `post()`

**Grund:** Bisher kann der Client nur lesen. Für den Versand von
WebUntis-Mitteilungen (Projekt `sprechtag`) wird POST mit JSON-Body
benötigt.

> **Achtung:** Schreibender Zugriff auf eine **undokumentierte**
> Schnittstelle. Aufrufer sollten den Status auswerten und mit
> Fehlschlägen rechnen – die erwartete Feldstruktur kann sich mit jedem
> WebUntis-Update ändern.

Einfügen direkt hinter der Methode `get()`:

```php
    /**
     * POST mit Bearer-Auth und JSON-Body.
     *
     * @param string $pfad  z. B. '/WebUntis/api/rest/view/v1/messages'
     * @param array  $daten wird als JSON gesendet
     * @return array{status:int, contentType:string, text:string, json:?array}
     */
    public function post(string $pfad, array $daten): array
    {
        $headers = ['Accept: application/json, text/plain',
                    'Content-Type: application/json'];
        if ($this->jwt !== null)      $headers[] = 'Authorization: Bearer ' . $this->jwt;
        if ($this->tenantId !== null) $headers[] = 'tenant-id: ' . $this->tenantId;
        if ($this->cookie !== null)   $headers[] = 'Cookie: ' . $this->cookie;

        $ch = curl_init($this->baseUrl . $pfad);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($daten, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
        ]);
        $text = curl_exec($ch);
        if ($text === false) {
            $fehler = curl_error($ch);
            curl_close($ch);
            return ['status' => 0, 'contentType' => '', 'text' => 'cURL: ' . $fehler, 'json' => null];
        }
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $ct     = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        $json = json_decode($text, true);
        return ['status' => $status, 'contentType' => $ct,
                'text' => $text, 'json' => is_array($json) ? $json : null];
    }
```

---

## Nach dem Einfügen

```bash
php -l src/WebUntisRest.php        # Syntaxprüfung
```

Version auf 1.3.0 setzen (composer.json bzw. Versionskonstante),
CHANGELOG ergänzen (`docs/CHANGELOG_ergaenzung.md`).
