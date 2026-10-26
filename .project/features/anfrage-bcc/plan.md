# Plan: anfrage-bcc

Branch: `feature/anfrage-bcc` · Spec: `spec.md` · Dashboard-Task #84

## Aufgaben
- [x] T1 (AK1–AK3, Randfälle 1–3) test-writer — Tests: `tests/Feature/AnfrageBccTest.php`
- [x] T2 (AK1–AK4) [pro] Config-Schlüssel `mailBcc` in `src/Config/config.php`, `bcc()` in `src/Mail/Anfrage.php`, README (Abschnitt Konfiguration) — Tests: `tests/Feature/AnfrageBccTest.php`
- [x] T3 NACHWEIS (Moderator): GATE-Log, Tests des Pakets, ganze Suite
- [x] T4 REVIEW (reviewer) → `pruefung.md`

## Stand
T1–T4 erledigt. Ganze Host-Suite 612 grün. REVIEW freigegeben (`pruefung.md`), AK1–AK4 und Randfälle bestanden. Hinweise aus dem Review: ungültige BCC-Adresse verhindert auch die Hauptmail (Folge-Spec vorgeschlagen), kein Test prüft den Envelope. Gemergt in main. Tag und Rollout nur auf Ansage des Users.
