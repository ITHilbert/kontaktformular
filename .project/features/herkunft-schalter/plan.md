# Plan: herkunft-schalter (Dashboard #110)

Branch: `fix/herkunft-schalter` (Paket kontaktformular, von `main`) · Spec: `spec.md` · Spec selbst freigegeben (Nachtschicht 2026-10-31)

## Aufgaben
- [ ] T1 (AK1–AK6) test-writer: Tests Schalter an/aus, Middleware-Gruppen (Router und später aufgelöster Kernel), Session, Spalte, Mail — Tests: `tests/Feature/HerkunftSchalterTest.php`
- [ ] T2 (AK1–AK7) [pro] Config `herkunft_erfassen` (Cast in `src/Config/config.php`), `KontaktformularServiceProvider` hängt `HerkunftErfassen` nur bei an ein, `KontaktformularController` liest die Session-Herkunft nur bei an; README — Tests: `tests/Feature/HerkunftSchalterTest.php`
- [ ] T3 NACHWEIS (Moderator)
- [ ] T4 REVIEW (reviewer, Review-Platz) → `pruefung.md`

## Stand
Plan angelegt.
