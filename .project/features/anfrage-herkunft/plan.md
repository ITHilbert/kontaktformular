# Plan: anfrage-herkunft

Branch: `feature/anfrage-herkunft` (nach Merge von `anfrage-bcc` auf `main` neu aufsetzen) · Spec: `spec.md` · Dashboard-Task #85

## Aufgaben
- [x] T1 (AK1–AK8, Randfälle 1–9) test-writer — Tests: `tests/Feature/AnfrageHerkunftTest.php`
- [ ] T2 (AK1–AK9) [senior] Migration `herkunft`, Model-Cast, Middleware samt Registrierung im ServiceProvider, Einordnung `Herkunft`, Controller, Mail-Views, README — Tests: `tests/Feature/AnfrageHerkunftTest.php`
- [ ] T3 NACHWEIS (Moderator): GATE-Log, Tests des Pakets, ganze Suite, echter Lauf im Host (Cookie-Header)
- [ ] T4 REVIEW (reviewer) → `pruefung.md`
- [ ] T5 Datenschutz (Moderator): Datenschutzerklärungen der 6 Sites auf einen Hinweis zur Herkunft prüfen, Textvorschlag an den User

## Stand
Spec freigegeben (User, 2026-10-26), Randfall 9 vom Moderator ergänzt (DSGVO). T1: 39 Tests, 37 rot (fehlende Funktion), 2 grün (AK7, Verneinungen mit Gegenprobe).
