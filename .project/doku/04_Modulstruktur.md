# Modulstruktur: ITHilbert Kontaktformular

```
src/
  Commands/          KontaktformularCommand.php (kontaktformular:delete_old_files)
  Config/            config.php (wird als config/kontaktformular.php veröffentlicht)
  Controllers/       KontaktformularController.php
  Database/
    Migrations/      Anlage der Tabelle, telefon nullable, Spalte herkunft
    Seeders/         DatabaseSeeder.php
    factories/       (leer, .gitkeep)
  Enums/             HerkunftKanal.php
  Mail/              Anfrage.php (Mailable)
  Middleware/        HerkunftErfassen.php
  Models/            Kontaktformular.php
  Resources/
    assets/          (leer, .gitkeep; veröffentlicht nach public/vendor/kontaktformular)
    lang/de|en/      kontaktformular.php
    schema/ui/       kontaktformular.json
    Vue/             kontaktformular.vue
    views/           danke_formular, danke_bot_formular, show
    views/mail/      anfrage (HTML), anfrage_plain (Text)
  Routes/            web.php (Formular und Downloads), api.php (ohne Routen)
  ValueObjects/      Herkunft.php, SiteAdresse.php
  KontaktformularServiceProvider.php
```

Routen: siehe [07_API-Definitionen.md](07_API-Definitionen.md). Tabelle: siehe [08_Datenbankschema.md](08_Datenbankschema.md).
