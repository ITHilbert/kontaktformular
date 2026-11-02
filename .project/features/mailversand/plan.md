# Plan: mailversand (Dashboard #316)

Branch: `fix/mailversand` (Paket kontaktformular, von `main`), Host `fix/kontaktformular-mailversand` · Spec: `spec.md` · Spec selbst freigegeben (Nachtschicht 2026-11-02)

## Aufgaben
- [ ] T1 (AK1–AK5) test-writer: Versandfehler (TransportException, RfcComplianceException) → Danke-Seite, einmal gespeichert, gemeldet mit id/nummer; ohne Fehler unverändert — Tests: `tests/Feature/MailversandFehlerTest.php`
- [ ] T2 (AK1–AK6) [pro] Versand in `KontaktformularController::anfrage()` abfangen, `report()` mit Bezug zur Anfrage, Weiterleitung `danke_formular`; README — Tests: `tests/Feature/MailversandFehlerTest.php`
- [ ] T3 NACHWEIS (Moderator)
- [ ] T4 REVIEW (reviewer, Review-Platz) → `pruefung.md`

Bestehende Tests: `tests/Feature/KontaktformularTest.php`, `tests/Feature/AnfrageBccTest.php`, `tests/Feature/AnhaengeDiskTest.php`

## Stand
Spec und Plan angelegt. Weiter: T1 test-writer.
