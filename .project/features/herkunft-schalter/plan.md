# Plan: herkunft-schalter (Dashboard #110)

Branch: `fix/herkunft-schalter` (Paket kontaktformular, von `main`) · Spec: `spec.md` · Spec selbst freigegeben (Nachtschicht 2026-10-31)

## Aufgaben
- [x] T1 (AK1–AK6) test-writer (33 Tests, 30 rot: Schlüssel `herkunft_erfassen` fehlt / Schalter-Gegenprobe null, Controller übernimmt Session-Herkunft; AK5 nur Regressionsschutz): Tests Schalter an/aus, Middleware-Gruppen (Router und später aufgelöster Kernel), Session, Spalte, Mail — Tests: `tests/Feature/HerkunftSchalterTest.php`
- [x] T1b (AK2) test-writer: zwei Router-Tests lösen den Kernel vorher auf (Gegenprobe griff nie; HANDOFF coder-pro) — Tests: `tests/Feature/HerkunftSchalterTest.php`
- [x] T2 (AK1–AK7) [pro] Config `herkunft_erfassen` (Cast in `src/Config/config.php`), `KontaktformularServiceProvider` hängt `HerkunftErfassen` nur bei an ein, `KontaktformularController` liest die Session-Herkunft nur bei an; README — Tests: `tests/Feature/HerkunftSchalterTest.php`
- [x] T3 NACHWEIS (Moderator)
- [x] T4 REVIEW (reviewer, Review-Platz) → `pruefung.md` — zweimal nicht freigegeben (Router-Gruppe, dann Router-Priorität ungetestet)
- [ ] T1c (AK2, AK6) test-writer: Testpaar mit HTTP-Kernel vor dem Boot (`#[DefineEnvironment]`, wie `public/index.php`), Router-Priorität bei aus ohne, bei an mit `HerkunftErfassen`, Gegenprobe `StartSession` in `$router->middlewarePriority`; Rot-Nachweis per Mutante B (nur Router-Priorität vor dem Schalter) — Tests: `tests/Feature/HerkunftSchalterTest.php`
- [ ] T5 Nachprüfung (reviewer, Review-Platz, `NACHPRUEFUNG: d02e9f6`)

Bestehende Tests: `tests/Feature/HerkunftSchalterTest.php`, `tests/Feature/AnfrageHerkunftTest.php`

## Stand
Nachtschicht 2026-11-02: main eingemergt (0b6497a, Host 8fab73a). Weiter: T1c test-writer (Lösungsweg aus der Nachprüfung, Produktivcode bleibt), danach T5.
