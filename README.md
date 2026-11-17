# ITHilbert Kontaktformular

**Beschreibung**: Kontaktformular mit Spam-Schutz, optionalem Datei-Upload und Mail-Versand.

## Features

- Felder: Name, E-Mail, Telefon (optional), Nachricht, Datei-Anhang (optional)
- Spam-Schutz: Honeypot-Feld + Mindest-Nachrichtenlänge + Rate-Limit (`throttle:6,1`)
- Datei-Upload mit Whitelist (`pdf,jpg,png,zip,doc,docx,xls,xlsx`), automatisches Zippen
- Token-basierter Download-Link mit Hash + Name + ID
- Cleanup-Command für alte Dateien (`kontaktformular:delete_old_files`)

## Installation

```bash
composer require ithilbert/kontaktformular
php artisan vendor:publish --tag=kontaktformular-config
php artisan migrate
```

## Konfiguration

```php
// config/kontaktformular.php
return [
    'mailFrom'        => env('KONTAKTFORMULAR_MAIL_FROM', env('MAIL_FROM_ADDRESS')),
    'mailTo'          => env('KONTAKTFORMULAR_MAIL_TO',   env('MAIL_TO_ADDRESS')),
    'mailBcc'         => env('KONTAKTFORMULAR_MAIL_BCC', ''),
    'subject'         => env('KONTAKTFORMULAR_SUBJECT', 'Anfrage von ' . env('APP_NAME')),
    'fileDownloadUrl' => env('APP_URL') . '/kontaktformular/file/',
    'dateien_loeschen_nach_tagen'  => env('KONTAKTFORMULAR_DATEIEN_LOESCHEN_NACH_TAGEN', 30),
    'anfragen_loeschen_nach_tagen' => env('KONTAKTFORMULAR_ANFRAGEN_LOESCHEN_NACH_TAGEN'),
    'disk'                         => env('KONTAKTFORMULAR_DISK', 'local'),
    'loeschen_einplanen'           => env('KONTAKTFORMULAR_LOESCHEN_EINPLANEN', true),
    'feld_website'                 => env('KONTAKTFORMULAR_FELD_WEBSITE', false),
    'nachricht_pflicht'            => env('KONTAKTFORMULAR_NACHRICHT_PFLICHT', true),
];
```

### Optionale Felder: Website und Nachricht

- `feld_website` (env `KONTAKTFORMULAR_FELD_WEBSITE`), Standard **aus**. An: Das Formular zeigt nach der Telefonnummer das optionale Feld „Adresse Ihrer Website“ (max. 2048 Zeichen). Der Wert wird in der Spalte `website` gespeichert (nullable, ohne Schema-Prüfung; Leerzeichen am Rand entfallen, Zeilenumbrüche werden je durch ein Leerzeichen ersetzt) und steht in der Mail als Zeile `Website:` vor `Mitteilung:`. **Vorher `php artisan migrate` ausführen** – ohne die Spalte `website` schlägt das Speichern bei aktivem Feld fehl. Aus: Das Feld und die Spalte werden nicht angefasst.
- `nachricht_pflicht` (env `KONTAKTFORMULAR_NACHRICHT_PFLICHT`), Standard **an**. Aus: Die Nachricht ist optional (Label „Ihre Nachricht (optional)“), eine leere Anfrage wird gespeichert (Nachricht `''`) und verschickt, die Mail enthält dann keine Zeile `Mitteilung:`. Eine ausgefüllte Nachricht geht weiter durch die Bot-Prüfung (mindestens 10 Zeichen, mindestens ein Leerzeichen); das Honeypot-Feld `fax` greift immer.
- Leerzeichen und Zeilenumbrüche am Anfang und Ende der Nachricht werden beim Speichern entfernt. Wird die Nachricht nicht als Zeichenkette gesendet (z. B. als Array), behandelt das Paket die Anfrage wie eine Bot-Anfrage: Weiterleitung auf die Bot-Danke-Seite, kein Datensatz, keine Mail.
- Auswertung der Variablen wie bei `KONTAKTFORMULAR_HERKUNFT_ERFASSEN`: `false`, `0`, `off`, `no`, leer = aus; `true`, `1`, `on`, `yes` = an.
- Die Standard-View `kontaktformular::show` reicht beide Werte als Props `feld-website` und `nachricht-pflicht` an die Vue-Komponente durch. Wer die Komponente selbst einbindet (eigene Kopie der View), setzt diese Props selbst. Hosts mit eigener Kopie der Komponente (`kontaktformular.vue`) bauen das Feld bzw. die Props `feld-website` und `nachricht-pflicht` selbst ein.
- Hosts, die die Komponente in ihr Bundle bauen, müssen **neu bauen**; veröffentlichte Mail-Views müssen neu veröffentlicht werden.
- Datenschutz: Die Website-Adresse ist eine zusätzlich erhobene personenbezogene Angabe – die Datenschutzerklärung des Projekts bei Aktivierung prüfen und ergänzen.

Schlägt der Mailversand an `mailTo`/`mailBcc` fehl (z. B. SMTP-Fehler, ungültige BCC-Adresse), hält das den Besucher nicht auf: Er sieht die Danke-Seite, der Fehler wird gemeldet (Log, mit id und Nummer der Anfrage) und die Anfrage bleibt gespeichert.

### Kopie an das Lead-Postfach (`mailBcc`)

`mailBcc` (env `KONTAKTFORMULAR_MAIL_BCC`) schickt jede Anfrage zusätzlich als BCC-Kopie an ein Lead-Postfach, z. B. `marketing@it-entwickler-hilbert.de` für die Marketing-Auswertung. `mailTo`, Absender, Betreff und Inhalt bleiben unverändert.

- Eine Adresse: `KONTAKTFORMULAR_MAIL_BCC=marketing@it-entwickler-hilbert.de`
- Mehrere Adressen: durch Komma getrennt (`a@x.de, b@y.de`); Leerzeichen und leere Teile werden ignoriert. In der veröffentlichten Config ist auch ein Array möglich (`['a@x.de', 'b@y.de']`).
- Leer oder nicht gesetzt: keine Kopie.

## Herkunft der Anfrage (Google Ads / UTM)

Das Paket merkt sich, woher ein Besucher kam, und schickt diese Herkunft mit der Anfrage mit – auch wenn er vor dem Formular weitere Seiten aufruft.

### Update eines bestehenden Projekts

- `php artisan migrate` ist beim Update **Pflicht** (neue Spalte `herkunft`); ohne sie schlägt das Speichern fehl, sobald eine Herkunft erfasst wurde.
- Wer die Mail-Views veröffentlicht hat (`php artisan vendor:publish --tag=kontaktformular-views`), muss sie **neu veröffentlichen** (`--force`) und eigene Anpassungen übernehmen – sonst fehlen die neuen Zeilen in der Mail.

### Middleware

Die Middleware `ITHilbert\Kontaktformular\Middleware\HerkunftErfassen` wird vom ServiceProvider **automatisch in die Middleware-Gruppe `web` eingehängt**, und zwar **direkt nach `StartSession`** (in der Gruppe und in der Middleware-Priorität). So wird die Herkunft auch erfasst, wenn CSRF-Prüfung, `auth` oder `throttle` den Request danach abbrechen. Middlewares, die andere Pakete in `web` einhängen (z. B. `userauth`), bleiben erhalten. Im Projekt ist nichts einzutragen. Routen außerhalb von `web` (z. B. `api`, ohne Session) bleiben unberührt. Erfasst werden nur Seitenaufrufe: Requests mit einer anderen Methode als `GET`/`HEAD` (z. B. `POST /anfrage`), Requests, die JSON erwarten (`expectsJson()`), und der Token-Abruf `kontaktformular.csrf_token` setzen keine Herkunft; der nächste Seitenaufruf der Session wird als erster Kontakt erfasst.

Erfasst wird nur beim **ersten Request einer Session** (First Touch); spätere Requests ändern die gespeicherten Werte nicht. Ist der erste Request schon `POST /anfrage`, zählt dieser.

### Erfasste Werte

| Wert | Quelle |
|---|---|
| `gclid`, `gbraid`, `wbraid` | Query-Parameter (Google-Ads-Klick-IDs) |
| `utm_source`, `utm_medium`, `utm_campaign`, `utm_term` | Query-Parameter |
| `einstieg` | Pfad der ersten Seite ohne Query, URL-dekodiert, z. B. `/excel-programmierer`, `/über-uns` (statt `/%C3%BCber-uns`) |
| `referrer` | nur der Host aus dem `Referer`-Header, z. B. `google.com` |

- Nur vorhandene Werte werden gespeichert. Leere Parameter und Arrays (`?gclid[]=x`) werden ignoriert, Werte über 255 Zeichen auf 255 gekürzt.
- Ein Referrer mit dem eigenen Host (interne Navigation) oder ohne gültigen Host gilt als kein Referrer.
- Steuerzeichen (U+0000–U+001F, U+007F, U+0080–U+009F) sowie die Zeilentrenner U+2028 und U+2029 werden aus allen erfassten Werten und aus `site` entfernt. So entstehen keine zusätzlichen Zeilen im Mail-Text.
- Als Klick-ID zählt jeder Parametername, den PHP als `gclid`, `gbraid` oder `wbraid` liest, z. B. `gcl%69d=…` oder `+gclid=…`.

### Herkunftserfassung abschalten

Config `kontaktformular.herkunft_erfassen` (env `KONTAKTFORMULAR_HERKUNFT_ERFASSEN`), Standard **an**. Zum Abschalten (z. B. aus Datenschutzgründen) `KONTAKTFORMULAR_HERKUNFT_ERFASSEN=false` setzen; ebenso aus sind `0`, `off`, `no` und ein leerer Wert. `true`, `1`, `on`, `yes` oder nicht gesetzt = an.

- Aus: Die Middleware `HerkunftErfassen` wird nicht eingehängt, es wird keine Herkunft in der Session gemerkt, und eine bereits vorhandene Session-Herkunft wird nicht übernommen. Die Spalte `herkunft` bleibt `null`.
- Die Mail zeigt dann `Herkunft: direkt`, `Einstieg: -`, `Referrer: -` und `Klick-ID: -`.
- Die Klick-IDs `gclid`, `gbraid` und `wbraid` werden aus `site` weiterhin entfernt.
- Maßgeblich ist der Wert beim Start der App; eine Änderung per `config([...])` zur Laufzeit wird für die Middleware nicht unterstützt.

### Spalte `herkunft`

Die Migration des Pakets legt die nullable JSON-Spalte `herkunft` in `kontaktformular` an (`php artisan migrate`). Sie enthält die erfassten Werte, z. B. `{"gclid":"…","utm_source":"google","einstieg":"/excel-programmierer","referrer":"google.com"}`. Ohne gespeicherte Herkunft bleibt sie `null`; bestehende Anfragen werden nicht nachbefüllt.

### Zeilen in der Mail

HTML- und Text-Teil der Mail enthalten direkt nach `Site:` vier Zeilen:

```
Herkunft: google_ads
Einstieg: /excel-programmierer
Referrer: google.com
Klick-ID: gclid
```

- `Herkunft:` ist `google_ads`, wenn `gclid`, `gbraid` oder `wbraid` vorhanden ist oder `utm_source=google` und `utm_medium=cpc` (Groß-/Kleinschreibung egal); sonst `organisch/sonstige`, wenn ein Referrer vorhanden ist; sonst `direkt`.
- `Klick-ID:` nennt nur den **Namen** des Parameters (bei mehreren den ersten in der Reihenfolge `gclid`, `gbraid`, `wbraid`), nie seinen Wert.
- Fehlt ein Wert für `Einstieg:`, `Referrer:` oder `Klick-ID:`, steht dort `-`.
- Die Klick-IDs `gclid`, `gbraid` und `wbraid` werden beim Speichern aus der übermittelten Seitenadresse (Formularwert `site`, Spalte `url`, Zeile `Site:`) **entfernt**. Alle übrigen Bestandteile bleiben unverändert, auch `utm_*`; war die Klick-ID der einzige Parameter, entfällt das `?`.

### Hinweis für die Datenschutzerklärung

- Die Herkunft wird **serverseitig in der Laravel-Session** gemerkt (die es ohnehin gibt) und mit der Anfrage in der Datenbank gespeichert.
- Es werden **keine neuen Cookies** gesetzt; das Paket schreibt nichts in `localStorage` oder `sessionStorage` und liefert dafür kein JavaScript aus.
- Beim Session-Treiber `cookie` (`SESSION_DRIVER=cookie`) wird **nichts erfasst**, weil die Session-Daten dann im Browser lägen. Die Anfrage wird dann mit `herkunft = null` gespeichert, die Mail zeigt `direkt` bzw. `-`.
- Gespeichert werden die Klick-ID (z. B. `gclid`), UTM-Parameter, die Einstiegsseite und der Host der verweisenden Seite. Die Datenschutzerklärung der Site sollte diese Verarbeitung im Abschnitt zum Kontaktformular nennen.

## Löschfristen

| Einstellung (`.env`) | Standard | Wirkung |
|---|---|---|
| `KONTAKTFORMULAR_DATEIEN_LOESCHEN_NACH_TAGEN` | `30` | Anhänge älter als die Frist werden gelöscht, die Anfrage bleibt (`file_deleted_at` wird gesetzt). |
| `KONTAKTFORMULAR_ANFRAGEN_LOESCHEN_NACH_TAGEN` | nicht gesetzt | Anfragen älter als die Frist werden samt Anhang gelöscht. Ohne Wert bleiben Anfragen dauerhaft erhalten. |

Beide Werte müssen positive ganze Zahlen sein. Bei ungültigem Wert bricht der Command mit Fehlermeldung ab (Exit-Code 1) und löscht nichts.

**Achtung:** `KONTAKTFORMULAR_DATEIEN_LOESCHEN_NACH_TAGEN=` ohne Wert gilt als ungültig – dann stoppt auch die Löschung der Anhänge. Die Zeile entweder mit Zahl setzen oder ganz weglassen.

Anhänge liegen auf der Disk `KONTAKTFORMULAR_DISK` (Standard `local`) unter `kontaktformular/<hash>.zip`; bei Laravel 11+ ist das physisch `storage/app/private/kontaktformular/`. Die Disk braucht einen lokalen Pfad (das ZIP entsteht mit `ZipArchive`); Remote-Disks wie S3 werden nicht unterstützt. Vorhandene Anhänge werden nicht verschoben.

## Command

```bash
php artisan kontaktformular:delete_old_files
```

Löscht zuerst die Anfragen nach der Anfragenfrist, danach die Anhänge nach der Dateifrist, und gibt die Zahl gelöschter Anhänge und Anfragen aus. Eine bereits fehlende Anhang-Datei ist kein Fehler.

**Scheduler:** Das Paket plant den Command täglich selbst ein. Mit `KONTAKTFORMULAR_LOESCHEN_EINPLANEN=false` (auch `0`, `off`) entfällt die Einplanung. Plant ein Projekt ihn zusätzlich selbst ein, ist die doppelte Ausführung harmlos.

## Routen

```php
Route::post('anfrage', [KontaktformularController::class, 'anfrage'])->name('anfrage');
Route::get('kontaktformular/csrf-token', [KontaktformularController::class, 'csrf_token'])->name('kontaktformular.csrf_token');
Route::get('danke-formular', [KontaktformularController::class, 'danke_formular'])->name('danke_formular');
Route::get('danke-bot-formular', [KontaktformularController::class, 'danke_bot_formular'])->name('danke_bot_formular');
Route::get('kontaktformular/file/{hash}/{name}/{id}', [KontaktformularController::class, 'file_download'])->name('kontaktformular.file');
```

### CSRF-Token auffrischen

`GET /kontaktformular/csrf-token` (Name `kontaktformular.csrf_token`) liefert `{"token": "..."}` mit `Cache-Control: no-store`. Die Vue-Komponente ruft sie beim Absenden auf und setzt das Token neu, damit eine abgelaufene Sitzung (z. B. Seite per „Zurück“ aus dem Cache) nicht zu 419 führt. Kommt die Seite per „Zurück“ aus dem Browser-Cache (`pageshow` mit `persisted`) und blieb der JS-Zustand erhalten, wird das Absenden wieder freigegeben. Schlägt der Abruf fehl, wird mit dem vorhandenen Token abgesendet. Die Route gilt nur für die gleiche Herkunft (kein CORS); die CSRF-Prüfung von `POST /anfrage` bleibt unverändert. Die URL kommt als Prop `token-route` (Vorgabe `/kontaktformular/csrf-token`).

**Update:** Hosts, die die Komponente (`src/Resources/Vue/kontaktformular.vue`) in ihr Bundle bauen, müssen nach dem Update neu bauen (`npm run production`).

### WebMCP (KI-Agenten im Browser)

Das Formular trägt deklarative WebMCP-Attribute: `toolname` und `tooldescription` am `<form>`, `toolparamdescription` an jedem Feld. Ein Agent kann das Formular so ausfüllen. Es gibt kein `toolautosubmit`: Der Agent sendet nie selbst, die Person klickt „Anfrage senden“.

- Config `webmcp_name` (env `KONTAKTFORMULAR_WEBMCP_NAME`), Standard `null` = `kontaktformular_senden`. Wird als Prop `tool-name` durchgereicht.
- Config `webmcp_beschreibung` (env `KONTAKTFORMULAR_WEBMCP_BESCHREIBUNG`), Standard `null` = deutsche Standardbeschreibung. Prop `tool-description`.
- Das Feld `fax` (Honeypot, Spamschutz) bleibt leer. Die Einwilligung in die Datenverarbeitung muss die Person selbst geben; die Checkbox hat kein `required` mehr, `absenden()` prüft sie und zeigt sonst die Meldung per `setCustomValidity`/`reportValidity`.
- Restrisiko: Füllt ein Agent den Honeypot trotzdem, gilt die Anfrage wie bisher als Bot.

Hosts mit eigener Kopie der Komponente müssen die WebMCP-Attribute (toolname, tooldescription, toolparamdescription) selbst einbauen und neu bauen (`npm run production`).

## Views

Im Hauptprojekt müssen `layouts.master` und `layouts.masterBot` existieren.

## Namespace
`ITHilbert\Kontaktformular`
