# Plan: download-route

Branch: `test/download-route` · ohne Verhaltensänderung (nur Tests für bestehendes Verhalten), daher keine Spec

## Erwartetes Verhalten (`KontaktformularController::file_download`, Route `kontaktformular/file/{hash}/{name}/{id}`)
- Unbekannte `id`, falscher `hash` oder falscher `name` → 404
- Eintrag passt, Datei `storage/app/kontaktformular/<hash>.zip` fehlt → 410
- Eintrag passt, Datei vorhanden → Download als `<name>.zip`, `file_downloaded_at` gesetzt

## Aufgaben
- [x] T1 test-writer: Tests für alle Fälle, laufen über die Testsuite von packages_dev — Tests: tests/Feature/DownloadRouteTest.php
- [x] T2 NACHWEIS (Moderator): Tests grün über packages_dev; schlägt einer fehl → Bugfix

## Stand
Anlass: todo.md it-hilbert.com Nr. 9 (2026-10-23). T1: 10 Tests (je Aussage einer), grün – Verhalten wie erwartet, kein Fehler. NACHWEIS: Datei und Testsuite „Kontaktformular“ über packages_dev grün, Testdateien unter storage/app/kontaktformular aufgeräumt. Fertig.
