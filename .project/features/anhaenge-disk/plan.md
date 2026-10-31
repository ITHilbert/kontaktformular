# Plan: anhaenge-disk

Branch: `feature/anhaenge-disk` (Paket kontaktformular) · Spec: `spec.md` · Dashboard #119 · Spec selbst freigegeben (Nachtschicht 2026-10-31)

## Aufgaben
- [x] T1 (AK1–AK6) test-writer (c5bfccf, 30 rot, 6 grün = Bestand AK5/ZIP-Randfall): Tests für Upload, Download, Löschen mit Disk-Root `storage/app/private` und `storage/app`, Einplanung im Scheduler — Tests: `tests/Feature/AnhaengeDiskTest.php`
- [x] T2 (AK1–AK6) [pro] (c6ccf09, HANDOFF: 10 Tests rot, Ursache in Tests) Config `disk` und `loeschen_einplanen` in `src/Config/config.php`; Pfadlogik an einer Stelle über `Storage::disk()`, genutzt von Controller (Speichern, ZIP, Download) und `KontaktformularCommand`; Einplanung im `KontaktformularServiceProvider`; README (Konfiguration, Scheduler-Hinweis „Host braucht `schedule:run`, bei Plesk eigene Aufgabe“, Remote-Disks nicht unterstützt) — Tests: `tests/Feature/AnhaengeDiskTest.php`
- [x] T1b (AK1, AK3, AK5) test-writer (936ac5d): Korrektur nach HANDOFF Coder (c6ccf09): 2 Tests in AnhaengeDiskTest (forceFill auf nicht geänderten Wert schreibt nicht), Alt-Tests mit festem `storage_path('app/kontaktformular')` auf die Disk umstellen — Tests: `tests/Feature/DownloadRouteTest.php`, `tests/Feature/LoeschfristTest.php`, `tests/Feature/AnhaengeDiskTest.php`
- [x] T3 NACHWEIS (Moderator): Pint-Fund `no_unused_imports` per [junior] behoben (0bf8c2a)
- [ ] T4 REVIEW (reviewer, Review-Platz) → `pruefung.md`

## Stand
T1–T3 erledigt. NACHWEIS (Moderator, ohne GATE-Eintrag – die Subagent-Hooks schrieben in dieser Session nichts ins GATE-Log): Testsuite `Kontaktformular` im Host 607 grün; ganze Host-Suite 1245 grün; Pint der geänderten Dateien grün; Coder-Commit c6ccf09 ändert keine Tests; AK1–AK6 je in mindestens 5 Tests; echter Lauf `php artisan schedule:list` im Host zeigt `kontaktformular:delete_old_files` täglich (0 0 * * *), mit `KONTAKTFORMULAR_LOESCHEN_EINPLANEN=off` fehlt er. Die 8 Alt-Tests (DownloadRouteTest, LoeschfristTest) waren rot, weil das Testbench-Skelett `local` unter `storage/app/private` führt. Annahme: leeres `KONTAKTFORMULAR_LOESCHEN_EINPLANEN` = false; leere `KONTAKTFORMULAR_DISK` nicht gesondert behandelt. Weiter: T4 REVIEW (wartet auf Review-Platz unter ~/projekte/.worktrees nach Template-Abgleich).
