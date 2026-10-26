# Plan: anfrage-bcc

Branch: `feature/anfrage-bcc` · Spec: `spec.md` · Dashboard-Task #84

## Aufgaben
- [x] T1 (AK1–AK3, Randfälle 1–3) test-writer — Tests: `tests/Feature/AnfrageBccTest.php`
- [ ] T2 (AK1–AK4) [pro] Config-Schlüssel `mailBcc` in `src/Config/config.php`, `bcc()` in `src/Mail/Anfrage.php`, README (Abschnitt Konfiguration) — Tests: `tests/Feature/AnfrageBccTest.php`
- [ ] T3 NACHWEIS (Moderator): GATE-Log, Tests des Pakets, ganze Suite
- [ ] T4 REVIEW (reviewer) → `pruefung.md`

## Stand
Spec freigegeben (User, 2026-10-26). T1: 17 Tests, 14 rot (fehlendes BCC), 3 grün (AK3, Regressionsschutz Format).
