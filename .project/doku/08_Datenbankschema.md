# Datenbankschema: ITHilbert Kontaktformular

Tabelle `kontaktformular`. Angelegt durch `2023_08_15_143636_create_kontaktformular_table.php`, geändert durch `2026_05_22_120000_make_telefon_nullable_on_kontaktformular_table.php` und `2026_10_26_120000_add_herkunft_to_kontaktformular_table.php`.

| Spalte | Typ | Null | Standard | Hinweis |
|---|---|---|---|---|
| `id` | bigint unsigned | nein | auto | Primärschlüssel |
| `jahr` | integer | nein | – | Wird im Model auf das aktuelle Jahr gesetzt. |
| `nummer` | integer | nein | – | Laufnummer pro Jahr, im Model als `max(nummer) + 1` für `jahr` ermittelt. |
| `url` | string | nein | – | Seitenadresse ohne `gclid`, `gbraid`, `wbraid`. |
| `herkunft` | json | ja | `null` | Erfasste Herkunft, z. B. `{"gclid":"…","utm_source":"google","einstieg":"/excel-programmierer","referrer":"google.com"}`. Migration `2026_10_26_120000`, Position nach `url`. |
| `name` | string | nein | – | |
| `email` | string | nein | – | |
| `telefon` | string | ja | `null` | Nullable seit `2026_05_22_120000`. |
| `nachricht` | longText | nein | – | |
| `datenverarbeitung` | boolean | nein | `false` | Zustimmung zur Datenverarbeitung. |
| `file_name` | string | ja | `null` | Name des Anhangs ohne Endung. |
| `file_type` | string | ja | `null` | Ursprüngliche Dateiendung. |
| `file_hash` | string | ja | `null` | Dateiname unter `storage/app/kontaktformular/`, ohne Endung. |
| `file_size` | string | ja | `null` | In der Migration als string angelegt; der Controller speichert MB als Zahl. |
| `file_downloaded_at` | timestamp | ja | `null` | Zeitpunkt des letzten Downloads. |
| `file_deleted_at` | timestamp | ja | `null` | Gesetzt, wenn der Anhang durch den Löschbefehl entfernt wurde. |
| `created_at`, `updated_at` | timestamp | ja | – | Laravel-Timestamps. |

## Indizes und Besonderheiten

- Außer dem Primärschlüssel gibt es keine Indizes. Es gibt keinen Unique-Index auf `(jahr, nummer)`.
- `nummer` wird per `max + 1` ermittelt und nicht über eine Datenbanksequenz. Parallele Anfragen können dieselbe Nummer erhalten.
- `url` ist `string` (Standard 255 Zeichen), die Validierung von `site` erlaubt aber bis 2048 Zeichen. Längere Adressen führen bei striktem SQL-Modus zu einem Fehler beim Speichern.
- `herkunft` wird nicht nachträglich für bestehende Anfragen befüllt.
