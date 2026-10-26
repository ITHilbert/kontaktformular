# Plan: anfrage-herkunft

Branch: `feature/anfrage-herkunft` (nach Merge von `anfrage-bcc` auf `main` neu aufsetzen) · Spec: `spec.md` · Dashboard-Task #85

## Aufgaben
- [x] T1 (AK1–AK8, Randfälle 1–9) test-writer — Tests: `tests/Feature/AnfrageHerkunftTest.php`
- [x] T2 (AK1–AK9) [senior] Migration `herkunft`, Model-Cast, Middleware samt Registrierung im ServiceProvider, Einordnung `Herkunft`, Controller, Mail-Views, README — Tests: `tests/Feature/AnfrageHerkunftTest.php`
- [x] T3 NACHWEIS (Moderator): GATE-Log, Tests des Pakets, ganze Suite, echter Lauf im Host (Cookie-Header)
- [ ] T4 REVIEW (reviewer) → `pruefung.md`
- [ ] T5 Datenschutz (Moderator): Datenschutzerklärungen der 6 Sites auf einen Hinweis zur Herkunft prüfen, Textvorschlag an den User

## Stand
T1–T3 erledigt. coder-senior: HANDOFF mit zwei berechtigten Test-Reklamationen (withHeaders blieb für Folgerequests gesetzt; alte Test-Schemas ohne Spalte `herkunft`), vom test-writer behoben (13d31fb). Danach GATE GRÜN (39 passed, Pint ok), Paket-Suite 94 grün. Echter Lauf im Host (`php artisan serve`): Set-Cookie mit und ohne `?gclid` identisch (Session, XSRF-TOKEN, Debugbar), Herkunft nur in der Session-Datei, laravel.log ohne Einträge.
Auslegungen des Coders (keine AK-Änderung): Registrierung zusätzlich am HTTP-Kernel (Testbench), Randfall 9 über `CookieSessionHandler`, Werte getrimmt, Referrer-Host per FILTER_VALIDATE_DOMAIN (IPv6/Umlaut = kein Referrer), Herkunft bleibt nach dem Absenden in der Session.
Rollout-Hinweise: `php artisan migrate` ist Pflicht (sonst 500 bei jeder Anfrage), veröffentlichte Mail-Views neu veröffentlichen. Nächster Schritt: ganze Suite, REVIEW.
