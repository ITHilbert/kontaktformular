# Kontext: ITHilbert Kontaktformular

Paket `ithilbert/kontaktformular` (Namespace `ITHilbert\Kontaktformular`). Stellt ein Kontaktformular mit Spam-Schutz, optionalem Datei-Upload und Mail-Versand bereit. Anfragen werden in der Tabelle `kontaktformular` gespeichert. Die Herkunft einer Anfrage (Google Ads, UTM, Einstiegsseite, Referrer) wird pro Session beim ersten Request erfasst.

## Startpunkt

- Installation, Konfiguration und Update-Hinweise stehen in der `README.md` und werden hier nicht wiederholt:
  - Installation: [README.md#installation](../../README.md#installation)
  - Konfiguration: [README.md#konfiguration](../../README.md#konfiguration)
  - Update eines bestehenden Projekts (Migration, Mail-Views): [README.md#update-eines-bestehenden-projekts](../../README.md#update-eines-bestehenden-projekts)

## Leitdokumente

- [01_Architektur.md](01_Architektur.md): Bausteine und Datenfluss einer Anfrage
- [04_Modulstruktur.md](04_Modulstruktur.md): Verzeichnisse unter `src/`
- [07_API-Definitionen.md](07_API-Definitionen.md): Routen und Request-Felder
- [08_Datenbankschema.md](08_Datenbankschema.md): Tabelle `kontaktformular`

## Entscheidungen

Verbindliche Entscheidungen liegen in [decisions/index.md](decisions/index.md).
