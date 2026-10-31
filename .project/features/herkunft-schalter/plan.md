# Plan: herkunft-schalter (Dashboard #110)

Branch: `fix/herkunft-schalter` (Paket kontaktformular, von `main`) · Spec: `spec.md` · Spec selbst freigegeben (Nachtschicht 2026-10-31)

## Aufgaben
- [x] T1 (AK1–AK6) test-writer (33 Tests, 30 rot: Schlüssel `herkunft_erfassen` fehlt / Schalter-Gegenprobe null, Controller übernimmt Session-Herkunft; AK5 nur Regressionsschutz): Tests Schalter an/aus, Middleware-Gruppen (Router und später aufgelöster Kernel), Session, Spalte, Mail — Tests: `tests/Feature/HerkunftSchalterTest.php`
- [ ] T2 (AK1–AK7) [pro] Config `herkunft_erfassen` (Cast in `src/Config/config.php`), `KontaktformularServiceProvider` hängt `HerkunftErfassen` nur bei an ein, `KontaktformularController` liest die Session-Herkunft nur bei an; README — Tests: `tests/Feature/HerkunftSchalterTest.php`
- [ ] T3 NACHWEIS (Moderator)
- [ ] T4 REVIEW (reviewer, Review-Platz) → `pruefung.md`

## Stand
T1 erledigt. Weiter: T2 [pro].
