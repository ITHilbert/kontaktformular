# Plan: anfrage-herkunft

Branch: `feature/anfrage-herkunft` (nach Merge von `anfrage-bcc` auf `main` neu aufsetzen) · Spec: `spec.md` · Dashboard-Task #85

## Aufgaben
- [x] T1 (AK1–AK8, Randfälle 1–9) test-writer — Tests: `tests/Feature/AnfrageHerkunftTest.php`
- [x] T2 (AK1–AK9) [senior] Migration `herkunft`, Model-Cast, Middleware samt Registrierung im ServiceProvider, Einordnung `Herkunft`, Controller, Mail-Views, README — Tests: `tests/Feature/AnfrageHerkunftTest.php`
- [x] T3 NACHWEIS (Moderator): GATE-Log, Tests des Pakets, ganze Suite, echter Lauf im Host (Cookie-Header)
- [ ] T4 REVIEW (reviewer) → `pruefung.md`
- [ ] T5 Datenschutz (Moderator): Datenschutzerklärungen der 6 Sites auf einen Hinweis zur Herkunft prüfen, Textvorschlag an den User

## Abweichungen der Coder
- Abweichung a6c9dfa1926eff0a7: zurückgebaut (Kernel-Registrierung per callAfterResolving → afterResolving, cd9813c); übrige Auslegungen Spec ergänzt (K1–K4) — User: Freigabe offen
- Abweichung a90a699136a071462: Spec ergänzt (Entscheidungsspielraum 2810cab, K5) — User: Freigabe offen
- Abweichung acae0b2421929634c: „Klick-ID-Name exakt“ zurückgebaut (Randfall 10, kodierte Namen); übrige Spec ergänzt (K6, K7) — User: Freigabe offen
- Abweichung a2ddf20cdd2febb85: Spec ergänzt (K8); Auslegung „Session unbereinigt“ trifft nicht zu (Middleware schreibt über Herkunft::fromArray) — User: Freigabe offen

## Stand
Zweites REVIEW nicht freigegeben (AK4 Steuerzeichen, AK6 kodierter Klick-ID-Name). Optimierungslauf 2: Hook `abweichung-commit.sh` + GATE-Merker, Skill `sicherheit.md`, Agent `test-writer.md` (Template f65219c, in packages_dev abgeglichen 68e63a8). Randfall 10 (0c26614), Regressions-Tests 65b0969 (50 rot). Bugfix coder-pro: GATE GRÜN (127 passed, Pint ok), Paket-Suite 182 grün, Host-Integrationstest 2 grün. Echter Lauf (`/auth/social/confirm/abc%0AReferrer:%20evil.example?gcl%69d=…&utm_source=goo%0Agle`): Session `gclid`, `utm_source=google`, Einstieg ohne Steuerzeichen. Klarstellungen K1–K8 in der Spec, Freigabe User offen. Nächster Schritt: ganze Suite, drittes REVIEW.
