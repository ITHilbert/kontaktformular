# Plan: anfrage-herkunft

Branch: `feature/anfrage-herkunft` (nach Merge von `anfrage-bcc` auf `main` neu aufsetzen) · Spec: `spec.md` · Dashboard-Task #85

## Aufgaben
- [x] T1 (AK1–AK8, Randfälle 1–9) test-writer — Tests: `tests/Feature/AnfrageHerkunftTest.php`
- [x] T2 (AK1–AK9) [senior] Migration `herkunft`, Model-Cast, Middleware samt Registrierung im ServiceProvider, Einordnung `Herkunft`, Controller, Mail-Views, README — Tests: `tests/Feature/AnfrageHerkunftTest.php`
- [x] T3 NACHWEIS (Moderator): GATE-Log, Tests des Pakets, ganze Suite, echter Lauf im Host (Cookie-Header)
- [x] T4 REVIEW (reviewer) → `pruefung.md` (4. Prüfung freigegeben)
- [x] T5 Datenschutz (Moderator): Datenschutzerklärungen der 6 Sites auf einen Hinweis zur Herkunft prüfen, Textvorschlag an den User

## Abweichungen der Coder
- Abweichung a6c9dfa1926eff0a7: zurückgebaut (Kernel-Registrierung per callAfterResolving → afterResolving, cd9813c); übrige Auslegungen Spec ergänzt (K1–K4) — User: K1–K8 freigegeben 2026-10-26
- Abweichung a90a699136a071462: Spec ergänzt (Entscheidungsspielraum 2810cab, K5) — User: K1–K8 freigegeben 2026-10-26
- Abweichung acae0b2421929634c: „Klick-ID-Name exakt“ zurückgebaut (Randfall 10, kodierte Namen); übrige Spec ergänzt (K6, K7) — User: K1–K8 freigegeben 2026-10-26
- Abweichung a2ddf20cdd2febb85: Spec ergänzt (K8); Auslegung „Session unbereinigt“ trifft nicht zu (Middleware schreibt über Herkunft::fromArray) — User: K1–K8 freigegeben 2026-10-26

## Stand
Fertig. Vier Prüfungen: 1. AK1 Reihenfolge, AK6 Klick-ID über Site; dazu Optimierungslauf 1 (userauth-Middleware verworfen → Host-Integrationstest, Skill). 2. AK4 Steuerzeichen, AK6 kodierter Name → Optimierungslauf 2 (Hook abweichung-commit.sh). 3. AK6 `+gclid`/`gclid%00` → Optimierungslauf 3 (ein Parser je URL, Invarianten-Test). 4. freigegeben (62c35e7): Paket 565, Host 1124 grün, Fuzz 200 000 Queries ohne Klick-ID-Rest.
T5: Datenschutzerklärungen geprüft – keine erwähnt die Herkunftsauswertung; 4 Sites holen den Text zentral (consentLaw bzw. laravel-kit LegalTextPort), homepage-betreuung.com lokal; Textvorschlag an den User (Dashboard #85).
Rollout-Hinweise: `php artisan migrate` Pflicht, veröffentlichte Mail-Views neu veröffentlichen. Tag und Rollout nur auf Ansage des Users. Gemergt in main.
