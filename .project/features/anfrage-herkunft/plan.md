# Plan: anfrage-herkunft

Branch: `feature/anfrage-herkunft` (nach Merge von `anfrage-bcc` auf `main` neu aufsetzen) · Spec: `spec.md` · Dashboard-Task #85

## Aufgaben
- [x] T1 (AK1–AK8, Randfälle 1–9) test-writer — Tests: `tests/Feature/AnfrageHerkunftTest.php`
- [x] T2 (AK1–AK9) [senior] Migration `herkunft`, Model-Cast, Middleware samt Registrierung im ServiceProvider, Einordnung `Herkunft`, Controller, Mail-Views, README — Tests: `tests/Feature/AnfrageHerkunftTest.php`
- [x] T3 NACHWEIS (Moderator): GATE-Log, Tests des Pakets, ganze Suite, echter Lauf im Host (Cookie-Header)
- [ ] T4 REVIEW (reviewer) → `pruefung.md`
- [ ] T5 Datenschutz (Moderator): Datenschutzerklärungen der 6 Sites auf einen Hinweis zur Herkunft prüfen, Textvorschlag an den User

## Stand
T1–T3 erledigt, erstes REVIEW nicht freigegeben (AK1 Middleware-Reihenfolge, AK6 Klick-ID über `Site:`). Danach Optimierungslauf: Der erste Provider-Stand verwarf per `callAfterResolving` am aufgelösten Kernel die web-Middleware von userauth (2FA, Passwort-Richtlinie) – von GATE, Review und NACHWEIS nicht bemerkt. Maßnahmen: Host-Integrationstest `tests/Feature/WebMiddlewareIntegrationTest.php` (packages_dev, d1b4c92), Skill `tech-laravel/package-entwicklung.md` Abschnitt 5 (Template f8d32ec).
Spec geändert (2810cab, User-Freigabe): Klick-IDs aus `site`, Einstieg dekodiert, Middleware direkt nach StartSession. Bugfix-Runde: GATE coder-senior GRÜN (67 passed), Paket-Suite 122 grün, Host-Integrationstest 2 grün, route:list `danke-formular`: StartSession → HerkunftErfassen …, EnforcePasswordPolicy, TwoFactorMiddleware vorhanden. Echter Lauf: Cookies mit/ohne gclid identisch, auth-Route 302 erfasst Herkunft.
Auslegungen des Coders: ungültiges UTF-8 im Einstieg bleibt kodiert; Klick-ID-Name exakt (kein `gclid[]`, keine Kodierung); Fragment unberührt. Nächster Schritt: ganze Suite, erneutes REVIEW.
