# Plan: loeschfrist

Branch: `feature/loeschfrist` · Spec: `spec.md`

## Aufgaben
- [x] T1 (AK1–AK4) test-writer: `tests/Feature/LoeschfristTest.php` (22fd08a, Korrektur 2d931fd), rot nachgewiesen: 18 rot
- [x] T2 (AK1–AK4) [pro] Config-Schlüssel in `src/Config/config.php`, `KontaktformularCommand` liest die Fristen, löscht Anhänge und Anfragen, validiert die Werte, meldet Zahlen; README (Abschnitt Konfiguration/Commands) ergänzen
- [x] T3 GATE + NACHWEIS (Moderator): Tests des Pakets selbst ausführen (GATE prüft in Sessions aus it-hilbert das falsche Repo, siehe ToDo it-hilbert)
- [ ] T4 REVIEW (reviewer) → `pruefung.md`

## Stand
T1–T3 erledigt. Ein Test widersprach AK1/AK3 (Dateifrist nicht gesetzt) → vom test-writer korrigiert (2d931fd). NACHWEIS (Moderator, ohne GATE – greift im Paket nicht): `php artisan test --compact --testsuite=Kontaktformular` im Host packages_dev: 28 Tests, 0 failed (Status „deprecated“ kommt vom Host: `PDO::MYSQL_ATTR_SSL_CA`); `php -l` ok; Diff gegen Spec geprüft. Pint: Paket hat keine eigene `pint.json`, Stil des Pakets beibehalten. Nächster Schritt: T4 REVIEW.
