# Plan: mailversand (Dashboard #316)

Branch: `fix/mailversand` (Paket kontaktformular, von `main`), Host `fix/kontaktformular-mailversand` · Spec: `spec.md` · Spec selbst freigegeben (Nachtschicht 2026-11-02)

## Aufgaben
- [x] T1 (AK1–AK5) test-writer (43525e7, 14 rot „Expected 30x but received 500“, grünbar 17/17): Versandfehler (TransportException, RfcComplianceException) → Danke-Seite, einmal gespeichert, gemeldet mit id/nummer; ohne Fehler unverändert — Tests: `tests/Feature/MailversandFehlerTest.php`
- [x] T2 (AK1–AK6) [pro] (fc8bc7e, fbac851) Versand in `KontaktformularController::anfrage()` abfangen, `report()` mit Bezug zur Anfrage, Weiterleitung `danke_formular`; README — Tests: `tests/Feature/MailversandFehlerTest.php`
- [x] T3 NACHWEIS (Moderator)
- [x] T4 REVIEW (reviewer, Review-Platz) → `pruefung.md` — freigegeben 2026-11-02

Bestehende Tests: `tests/Feature/KontaktformularTest.php`, `tests/Feature/AnfrageBccTest.php`, `tests/Feature/AnhaengeDiskTest.php`

## Stand
T1–T3 erledigt. GATE a3e4c95e011108414 GRÜN (76 passed), Moderator-Lauf 76 grün. Fertig: Review freigegeben, Doku, Entscheidung 0004, gemergt in main (Nachtschicht 2026-11-02).

Echtlauf: Review-Platz, eigener Server 127.0.0.1:8197 mit MAIL_HOST=127.0.0.1 MAIL_PORT=1 → POST /anfrage 302 auf /danke-formular, Anfrage genau einmal gespeichert (id 5, nummer 5), laravel.log genau ein ERROR „Anfrage-Mail nicht versendet: Connection could not be established …“ mit {"id":5,"nummer":5}, previous TransportException.

Offener Punkt a3e4c95e011108414: zwei Log-Einträge (report + Log::error) — Spec-konform, aber doppelt und rohe Fremd-Ausnahme → nachgebessert: eigene Ausnahme `AnfrageMailNichtVersendet` mit `context()`, nur `report()` (entschieden vom Moderator nach Rule fehlerbehandlung.md, umgesetzt von coder-pro a3e4c95e011108414 in fbac851)
