# Plan: anhaenge-disk

Branch: `feature/anhaenge-disk` (Paket kontaktformular) · Spec: `spec.md` · Dashboard #119 · Spec selbst freigegeben (Nachtschicht 2026-10-31)

## Aufgaben
- [ ] T1 (AK1–AK6) test-writer: Tests für Upload, Download, Löschen mit Disk-Root `storage/app/private` und `storage/app`, Einplanung im Scheduler — Tests: `tests/Feature/AnhaengeDiskTest.php`
- [ ] T2 (AK1–AK6) [pro] Config `disk` und `loeschen_einplanen` in `src/Config/config.php`; Pfadlogik an einer Stelle über `Storage::disk()`, genutzt von Controller (Speichern, ZIP, Download) und `KontaktformularCommand`; Einplanung im `KontaktformularServiceProvider`; README (Konfiguration, Scheduler-Hinweis „Host braucht `schedule:run`, bei Plesk eigene Aufgabe“, Remote-Disks nicht unterstützt) — Tests: `tests/Feature/AnhaengeDiskTest.php`
- [ ] T3 NACHWEIS (Moderator)
- [ ] T4 REVIEW (reviewer, Review-Platz) → `pruefung.md`

## Stand
Spec und Plan angelegt.
