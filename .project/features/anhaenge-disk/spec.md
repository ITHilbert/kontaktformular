# Feature: Anhänge über die Storage-Disk statt fester Pfade (Dashboard #119)

## Ausgangslage
`attachUploadedFile()` speichert Uploads mit `$upload->store('kontaktformular')` auf der Standard-Disk `local`, liest und zippt sie danach aber unter `storage_path('app/kontaktformular/…')`. Download (`file_download`) und `kontaktformular:delete_old_files` nutzen denselben festen Pfad. In Laravel-11/12/13-Skeletten liegt die Disk `local` unter `storage/app/private` → ZIP fehlt, der Download-Link liefert 410, der Lösch-Command löscht nie etwas (Speicherfrist der Datenschutzerklärung verletzt). Betroffen u. a. taskID, auditAI, ms, newLV, homepage-betreuung.com, Dashboard; nicht betroffen: it-hilbert.com, it-entwickler-hilbert.de, excel-/vba-programmierer.com (`local` = `storage/app`).
Außerdem plant heute jedes Projekt den Lösch-Command selbst ein; mehrere tun es nicht.

## Akzeptanzkriterien
- **AK1 — Disk einstellbar:** Gegeben die Config `kontaktformular.disk` (env `KONTAKTFORMULAR_DISK`, Standard `local`) — Wenn das Paket Anhänge speichert, zippt, ausliefert oder löscht — Dann nutzt es ausschließlich diese Disk (Pfade unter `kontaktformular/` auf der Disk), keinen fest verdrahteten `storage_path('app/…')`.
- **AK2 — Upload bei Disk-Root `storage/app/private`:** Gegeben die Disk `local` mit Root `storage/app/private` — Wenn eine Anfrage mit einer Datei (kein ZIP) abgeschickt wird — Dann liegt auf der Disk `kontaktformular/<file_hash>.zip`, das ZIP enthält die Datei unter ihrem bereinigten Namen, die unverpackte Datei ist entfernt, und `file_name`, `file_hash`, `file_type`, `file_size` sind wie bisher gesetzt.
- **AK3 — Download bei Disk-Root `storage/app/private`:** Gegeben eine Anfrage aus AK2 — Wenn der Link aus der Mail (`kontaktformular.file` mit Hash, Name, ID) aufgerufen wird — Dann antwortet die Route mit 200 und dem ZIP als Download `<file_name>.zip`, und `file_downloaded_at` ist gesetzt. Fehlt die Datei auf der Disk → 410; falscher Hash, Name oder ID → 404 (wie bisher).
- **AK4 — Löschen bei Disk-Root `storage/app/private`:** Gegeben ein Anhang auf der Disk, dessen Anfrage älter als `dateien_loeschen_nach_tagen` ist — Wenn `kontaktformular:delete_old_files` läuft — Dann ist `kontaktformular/<file_hash>.zip` von der Disk entfernt und `file_deleted_at` gesetzt; jüngere Anhänge bleiben. Gleiches gilt für Anfragen, die nach `anfragen_loeschen_nach_tagen` samt Anhang gelöscht werden.
- **AK5 — Bestand bei Disk-Root `storage/app` unverändert:** Gegeben die Disk `local` mit Root `storage/app` und ein vorhandener Anhang `storage/app/kontaktformular/<hash>.zip` — Wenn hochgeladen, heruntergeladen oder gelöscht wird — Dann liegen neue Anhänge wie bisher unter `storage/app/kontaktformular/`, der vorhandene Anhang ist über seinen Link herunterladbar und wird nach Ablauf der Frist gelöscht.
- **AK6 — Löschung eingeplant:** Gegeben die Config `kontaktformular.loeschen_einplanen` (env `KONTAKTFORMULAR_LOESCHEN_EINPLANEN`, Standard `true`) — Wenn der Scheduler der App aufgebaut wird — Dann enthält er `kontaktformular:delete_old_files` täglich; bei `false` ist der Command nicht eingeplant.

## Randfälle
- Upload ist selbst ein ZIP → wird nicht erneut gepackt, liegt als `kontaktformular/<hash>.zip` auf der Disk (wie bisher).
- Anhang-Datei fehlt beim Löschen bereits → kein Fehler, Datensatz wird trotzdem behandelt (wie bisher).
- Ein Projekt plant den Command zusätzlich selbst ein (z. B. Plesk-Aufgabe bei it-hilbert.com) → doppelte Ausführung ist harmlos, der Command ist wiederholbar.
- `KONTAKTFORMULAR_LOESCHEN_EINPLANEN` als String (`false`, `0`, `off`) → wird als Bool gewertet (`filter_var(…, FILTER_VALIDATE_BOOL)`).

## Nicht-Ziele
- Disks ohne lokalen Pfad (S3 o. ä.): Das ZIP entsteht mit `ZipArchive` auf dem lokalen Dateisystem; eine Remote-Disk wird nicht unterstützt (README nennt das).
- Verschieben vorhandener Anhänge zwischen `storage/app` und `storage/app/private`.
- Umbenennen des Commands oder der Route.
- Release/Tag (nur auf Ansage des Users) und Rollout in die Projekte.

## Entscheidungsspielraum
- Keine neuen Pakete.
- Wie die Disk im Code gekapselt wird (z. B. kleine Klasse `Anhangablage`), entscheidet der Coder; Controller und Command dürfen die Pfadlogik nicht doppelt enthalten (Doppelaufruf-Veto).
- Uhrzeit der täglichen Einplanung frei (`daily()` genügt).
- Ersetzt das Nicht-Ziel „Einplanen im Scheduler (macht jedes Projekt selbst)“ aus `loeschfrist` — Anlass #119.
