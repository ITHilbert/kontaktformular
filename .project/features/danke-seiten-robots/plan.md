# Plan: danke-seiten-robots

Branch: `fix/danke-seiten-robots` · Spec: `spec.md`

## Aufgaben
- [x] T1 (AK1, Randfall 1) test-writer — Regressions-Test — Tests: `tests/Feature/DankeSeitenRobotsTest.php`
- [x] T2 (AK1, Randfall 1) [pro] Include `header.robots_disallow` in `src/Resources/views/danke_formular.blade.php` und `danke_bot_formular.blade.php` durch eigenes Robots-Meta-Tag ersetzen — Tests: `tests/Feature/DankeSeitenRobotsTest.php`
- [x] T3 NACHWEIS (Moderator): GATE-Log, Tests, echter Aufruf im Host packages_dev

## Stand
T1–T3 erledigt. GATE grün (6 Tests), Paket-Tests grün, echter Aufruf im Host packages_dev (Port 8004): beide Danke-Seiten 200 mit genau einem `noindex, nofollow`. Gemergt in main.
