# Plan: anfrage-bcc

Branch: `feature/anfrage-bcc` · Spec: `spec.md` · Dashboard-Task #84

## Aufgaben
- [x] T1 (AK1–AK3, Randfälle 1–3) test-writer — Tests: `tests/Feature/AnfrageBccTest.php`
- [x] T2 (AK1–AK4) [pro] Config-Schlüssel `mailBcc` in `src/Config/config.php`, `bcc()` in `src/Mail/Anfrage.php`, README (Abschnitt Konfiguration) — Tests: `tests/Feature/AnfrageBccTest.php`
- [x] T3 NACHWEIS (Moderator): GATE-Log, Tests des Pakets, ganze Suite
- [ ] T4 REVIEW (reviewer) → `pruefung.md`

## Stand
T1–T3 erledigt. GATE coder-pro GRÜN (17 passed, Pint ok), Paket-Suite 55 grün, echter Lauf im Host (Mailable mit/ohne mailBcc) ok, laravel.log ohne neue Einträge. BCC wird im Konstruktor gesetzt (Tests prüfen ohne render()). Host-Pint hat bestehende Zeilen in `config.php` umformatiert (Paket hat keine eigene pint.json, siehe Geplant im Index). Nächster Schritt: ganze Suite, REVIEW.
