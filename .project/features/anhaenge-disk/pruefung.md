# Prüfung: anhaenge-disk (#119) — 2026-10-31

Geprüft im Review-Platz `/home/joerg/projekte/.worktrees/packages_dev/reviewer`, Host `feature/anhaenge-disk` (d82910b), Paket kontaktformular e5bb06c, Diff `main...HEAD` im Paket. Im Feature-Ordner liegt kein `abdeckung.md`. Echter Lauf auf http://127.0.0.1:8102 (Review-DB `packages_dev_review`); die Disk `local` des Hosts hat den Root `storage/app` (Szenario AK5). Das Szenario Root `storage/app/private` (AK2–AK4) ist nur über Tests nachgewiesen. Diese laufen mit dem echten `local`-Treiber auf einem echten Temp-Verzeichnis (`<temp>/app/private`), nicht mit `Storage::fake`.

| AK / Aussage (Zitat) | Ergebnis | Nachweis |
|---|---|---|
| AK1 „Gegeben die Config `kontaktformular.disk` (env `KONTAKTFORMULAR_DISK`, Standard `local`)“ | bestanden | `src/Config/config.php` `env('KONTAKTFORMULAR_DISK', 'local')`; tinker ohne env → `local`, mit `KONTAKTFORMULAR_DISK=public` → `public`, Pfad `storage/app/public/kontaktformular/abc.zip`; Test `test_config_hat_standardwerte_disk_local_und_einplanen_true` grün |
| AK1 „Wenn das Paket Anhänge speichert, zippt, … Dann nutzt es ausschließlich diese Disk (Pfade unter `kontaktformular/` auf der Disk)“ | bestanden | `Anhangablage::speichern` (putFile + ZipArchive über `$disk->path()`); `test_upload_nutzt_die_konfigurierte_disk_und_nicht_die_standard_disk`: nur `kontaktformular/<hash>.zip` auf der eigenen Disk, `<temp>/app` leer |
| AK1 „… ausliefert …“ | bestanden | `test_download_nutzt_die_konfigurierte_disk` (200) und Gegenprobe `test_download_ignoriert_dateien_auf_der_standard_disk_wenn_andere_disk_konfiguriert` (Datei nur auf `local` → 410) grün |
| AK1 „… oder löscht …“ | bestanden | `test_loeschen_nutzt_die_konfigurierte_disk`: alt gelöscht, jung bleibt, gleichnamige Datei auf `local` unberührt |
| AK1 „keinen fest verdrahteten `storage_path('app/…')`“ | bestanden | `grep -rn "storage_path\|storage/app" src/` → kein Treffer; Controller und Command nutzen nur `Anhangablage` (Pfadlogik an einer Stelle, Doppelaufruf-Veto erfüllt) |
| AK2 „Dann liegt auf der Disk `kontaktformular/<file_hash>.zip`“ | bestanden | `test_upload_legt_zip_auf_der_disk_unter_kontaktformular_hash_zip_ab` (Root `app/private`: Datei vorhanden, `app/kontaktformular` existiert nicht) |
| AK2 „das ZIP enthält die Datei unter ihrem bereinigten Namen“ | bestanden | `test_zip_enthaelt_die_datei_unter_ihrem_bereinigten_namen` (1 Eintrag `MeinBericht1.pdf`, Inhalt byte-gleich); echter Lauf: Upload `Mein Bericht (1).pdf` → `unzip -l` zeigt nur `MeinBericht1.pdf`, Inhalt per `cmp` gleich |
| AK2 „die unverpackte Datei ist entfernt“ | bestanden | `test_unverpackte_datei_ist_entfernt` (`allFiles()` = nur das ZIP); echter Lauf: in `storage/app/kontaktformular/` liegt nur `<hash>.zip` |
| AK2 „und `file_name`, `file_hash`, `file_type`, `file_size` sind wie bisher gesetzt“ | bestanden | `test_upload_setzt_file_felder_wie_bisher`; echter Lauf: `file_name=MeinBericht1`, `file_type=pdf`, `file_size=0.29`, `file_hash` = Dateiname ohne Endung (40 Zeichen) |
| AK3 „Dann antwortet die Route mit 200 und dem ZIP als Download `<file_name>.zip`“ | bestanden | `test_download_antwortet_200_mit_zip_als_name_zip` (Inhalt = ZIP auf der Disk); echter Lauf GET `/kontaktformular/file/<hash>/MeinBericht1/1` → 200, `Content-Disposition: attachment; filename=MeinBericht1.zip`, Download per `cmp` gleich dem ZIP |
| AK3 „und `file_downloaded_at` ist gesetzt“ | bestanden | `test_download_setzt_file_downloaded_at`; echter Lauf: `file_downloaded_at` danach `2026-10-31 11:12:40` (vorher null) |
| AK3 „Fehlt die Datei auf der Disk → 410“ | bestanden | `test_fehlt_die_datei_auf_der_disk_antwortet_die_route_mit_410` (dazu bleibt `file_downloaded_at` null); im echten Lauf nicht geprüft, weil dafür im Platz eine Datei hätte gelöscht werden müssen |
| AK3 „falscher Hash, Name oder ID → 404 (wie bisher)“ | bestanden | `test_falscher_hash_name_oder_id_liefern_404_und_setzen_nichts`; echter Lauf: Hash+`x`, Name `anders`, ID 999 → jeweils 404 |
| AK4 „Dann ist `kontaktformular/<file_hash>.zip` von der Disk entfernt“ | bestanden | `test_loeschen_entfernt_alten_anhang_von_der_disk_und_setzt_file_deleted_at` (Root `app/private`) |
| AK4 „und `file_deleted_at` gesetzt“ | bestanden | derselbe Test: `file_deleted_at` vorher null, danach gesetzt |
| AK4 „jüngere Anhänge bleiben“ | bestanden | `test_juengere_anhaenge_bleiben` (29 Tage bleibt, 31 Tage weg); echter Lauf `kontaktformular:delete_old_files` mit frischer Anfrage → „Gelöschte Anhänge: 0“, ZIP bleibt |
| AK4 „Gleiches gilt für Anfragen, die nach `anfragen_loeschen_nach_tagen` samt Anhang gelöscht werden.“ | bestanden | `test_anfragen_nach_anfragenfrist_werden_samt_anhang_geloescht` (alt: Datensatz und ZIP weg; jung: beides bleibt) |
| AK5 „Dann liegen neue Anhänge wie bisher unter `storage/app/kontaktformular/`“ | bestanden | `test_bei_root_storage_app_liegen_neue_anhaenge_unter_storage_app_kontaktformular`, `test_zip_upload_bei_root_storage_app_liegt_unter_storage_app_kontaktformular`; echter Lauf (Host-Disk `local` = `storage/app`): ZIP unter `storage/app/kontaktformular/`, `storage/app/private` existiert nicht |
| AK5 „der vorhandene Anhang ist über seinen Link herunterladbar“ | bestanden | `test_vorhandener_anhang_bei_root_storage_app_ist_ueber_seinen_link_herunterladbar` (vorab abgelegte Datei, 200, Inhalt gleich, `file_downloaded_at` gesetzt); echter Lauf: Download 200 aus `storage/app/kontaktformular/` |
| AK5 „und wird nach Ablauf der Frist gelöscht“ | bestanden | `test_vorhandener_anhang_bei_root_storage_app_wird_nach_ablauf_der_frist_geloescht`, `test_bei_root_storage_app_werden_anfragen_nach_anfragenfrist_samt_anhang_geloescht` |
| AK6 „Gegeben die Config `kontaktformular.loeschen_einplanen` (env `KONTAKTFORMULAR_LOESCHEN_EINPLANEN`, Standard `true`)“ | bestanden | `config.php` `env('KONTAKTFORMULAR_LOESCHEN_EINPLANEN', true)`; Test zu den Standardwerten grün |
| AK6 „Dann enthält er `kontaktformular:delete_old_files` täglich“ | bestanden | echter Lauf `php artisan schedule:list` → `0 0 * * *  php artisan kontaktformular:delete_old_files`; `test_standardmaessig_ist_der_command_eingeplant`, `test_command_ist_taeglich_eingeplant` (genau 1 Event) |
| AK6 „bei `false` ist der Command nicht eingeplant“ | bestanden | echter Lauf `KONTAKTFORMULAR_LOESCHEN_EINPLANEN=false` / `0` / `off` → kein Eintrag, `true` → 1; `test_bei_false_ist_der_command_nicht_eingeplant` mit Gegenprobe |
| Randfall 1 „Upload ist selbst ein ZIP → wird nicht erneut gepackt“ | bestanden | `test_zip_upload_wird_nicht_erneut_gepackt_und_liegt_als_hash_zip_auf_der_disk` (Inhalt byte-gleich dem Upload) |
| Randfall 1 „liegt als `kontaktformular/<hash>.zip` auf der Disk (wie bisher)“ | bestanden | derselbe Test (`allFiles()` = nur `kontaktformular/<hash>.zip`) und die Variante mit Bestand-Root |
| Randfall 2 „Anhang-Datei fehlt beim Löschen bereits → kein Fehler“ | bestanden | `test_fehlende_anhang_datei_ist_kein_fehler_und_datensatz_wird_behandelt`, `test_fehlende_anhang_datei_verhindert_loeschen_der_anfrage_nicht` (Command `assertSuccessful`); `delete` des local-Treibers wirft bei fehlender Datei nicht |
| Randfall 2 „Datensatz wird trotzdem behandelt (wie bisher)“ | bestanden | dieselben Tests: `file_deleted_at` gesetzt bzw. Anfrage gelöscht |
| Randfall 3 „doppelte Ausführung ist harmlos, der Command ist wiederholbar“ | bestanden | `test_command_ist_wiederholbar` (zweiter Lauf erfolgreich, Zustand unverändert) |
| Randfall 4 „`KONTAKTFORMULAR_LOESCHEN_EINPLANEN` als String (`false`, `0`, `off`) → wird als Bool gewertet (`filter_var(…, FILTER_VALIDATE_BOOL)`)“ | bestanden | `KontaktformularServiceProvider::planeLoeschungEin` nutzt `filter_var(…, FILTER_VALIDATE_BOOL)`; DataProvider-Tests für `false`/`'false'`/`'0'`/`'off'`/`''` und `'true'`/`'1'`/`'on'`; echter Lauf `off`/`0`/`false` → nicht eingeplant |
| Nicht-Ziel „Disks ohne lokalen Pfad … (README nennt das)“ | bestanden | keine Remote-Unterstützung gebaut; README Zeile 113 nennt die Einschränkung |
| Nicht-Ziel „Verschieben vorhandener Anhänge“ | bestanden | kein Verschiebecode im Diff; README: „Vorhandene Anhänge werden nicht verschoben.“ |
| Nicht-Ziel „Umbenennen des Commands oder der Route“ | bestanden | Signatur `kontaktformular:delete_old_files` und Route `kontaktformular.file` unverändert (`route:list`) |
| Nicht-Ziel „Release/Tag … Rollout“ | bestanden | kein Tag, keine Versionsänderung im Diff |
| Entscheidungsspielraum „Keine neuen Pakete“ | bestanden | `composer.json` im Diff unverändert |

## Sicherheit
- Download: Der Datensatz wird per ID geladen, Hash und Name werden mit der DB verglichen, bevor ein Pfad entsteht. Der Pfad wird nur aus dem gespeicherten Hash gebildet, Path-Traversal über die Route ist damit ausgeschlossen. 404 und 410 verhalten sich wie bisher.
- Der Disk-Name kommt nur aus der Config, nicht aus der Anfrage.
- Der Dateiname im ZIP wird weiterhin per Regex bereinigt (unverändert); die Upload-Validierung (`mimes`, `max:10240`) ist unverändert.
- Kein neues Mass-Assignment, keine neue Ausgabe in Views.
- Hinweis (kein Mangel der Spec): Wer `KONTAKTFORMULAR_DISK=public` setzt, legt Anhänge im öffentlich verlinkten Ordner `storage/app/public` ab. Die README nennt das nicht ausdrücklich, die Spec regelt es nicht.

## Regressionen
- Volle Suite im Platz `php artisan test --parallel --compact`: 1245 Tests, **1 Failure** — `ITHilbert\Kontaktformular\Tests\Feature\AnfrageHerkunftTest::test_referrer_mit_eigenem_host_gilt_als_kein_referrer` (erwartet `direkt`, erhält `organisch/sonstige`). Die Ursache liegt in der Umgebung, nicht im Branch: `.env.testing` des Review-Platzes setzt `APP_URL=http://127.0.0.1:8102`, der Test nimmt `localhost` als eigenen Host an. Der Diff berührt weder die Herkunftserfassung noch diesen Test. Gegenprobe: `APP_URL=http://localhost php artisan test --parallel --compact` → **OK (1245 Tests, 11999 Assertions)**. Dazu Task #315.
- `AnhaengeDiskTest` 36/36 grün; `DownloadRouteTest` + `LoeschfristTest` 32/32 grün. Die Änderung an den beiden Alt-Tests stellt nur den Ablageort auf die Disk um (Root `private`), ohne Zusicherungen abzuschwächen.
- Pint (`--test`) auf den geänderten `src/`-Dateien: bestanden.
- Verhalten bei Disk-Root `storage/app` (Host) im echten Lauf unverändert: Ablageort, ZIP-Inhalt, Download, 404-Fälle.
- Echter Lauf: POST `/anfrage` endete mit **500**. Ursache laut `laravel.log` (11:12:21) ist die SMTP-Authentifizierung (535, Mailtrap-Zugangsdaten in der `.env` des Platzes ungültig). Der Fehler trat nach dem Speichern von Datensatz und Anhang auf. Das ist vorhandenes Verhalten (synchrones `Mail::send` nach `save()`) und nicht Teil dieses Diffs. Sonst steht nichts im Log. Dazu Task #316 (wartet auf User).
- Rückstand des echten Laufs im Platz: Anfrage id 1 in `packages_dev_review` und `storage/app/kontaktformular/0FUyjhUkUffa0wpGtT1Yb5UnbqgsxH250Q8j6A2B.zip`. Beides ist nicht gelöscht, weil der Reviewer nur `pruefung.md` schreibt.

## Neue Tasks: #315 Tests/AnfrageHerkunftTest – eigenen Host im Test setzen statt APP_URL aus der Umgebung zu erwarten (freigabe), #316 Anfrage – Fehler beim Mailversand nicht als 500 nach bereits gespeicherter Anfrage ausgeben (wartet)

## Fazit: freigegeben
Alle Aussagen aus AK1–AK6 und den Randfällen sind bestanden. Der einzige rote Test der Suite liegt außerhalb des Diffs, und `APP_URL` im Review-Platz erklärt ihn. Mit `APP_URL=http://localhost` ist die ganze Suite grün. Das weicht von „rot = nicht freigegeben“ ab, weil der Fehlschlag nachweislich nicht vom Branch kommt. Wendet der Moderator die Regel wörtlich an, lautet das Ergebnis „nicht freigegeben bis #315“, ohne Mangel am Feature.
