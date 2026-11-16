# Plan: anfrage-website-feld

Branch: `feature/anfrage-website-feld` (Paket-Repo kontaktformular) · Spec: `spec.md` · Dashboard-Task #1312
Spec und Plan selbst freigegeben (automode 2026-11-16-1519).

## Aufgaben
- [x] T1 (AK1–AK9, Randfälle) test-writer — Tests: `tests/Feature/AnfrageWebsiteFeldTest.php`
- [x] T2 (AK1–AK9) [pro] Config-Schlüssel, Migration `website`, Controller, Model-`fillable`, Mail-Views — Tests: `tests/Feature/AnfrageWebsiteFeldTest.php`
- [x] T3 (AK10, AK11) [junior] Vue-Komponente (Props, Feld, Label) und View `show.blade.php` — Tests: `tests/Feature/AnfrageWebsiteFeldTest.php`
- [x] T4 (AK12) [junior] README — Tests: `tests/Feature/AnfrageWebsiteFeldTest.php`
- [x] T5 NACHWEIS (Moderator): GATE-Log, Tests des Pakets, Vue-Komponente kompilieren und rendern
- [ ] T6 REVIEW am Ende des Stapels (neue Migration, Verhalten)

Bestehende Tests: `tests/Feature/KontaktformularTest.php`, `tests/Feature/AnfrageBccTest.php`, `tests/Feature/AnfrageHerkunftTest.php`, `tests/Feature/MailversandFehlerTest.php`

## Stand
T1–T5 erledigt (Coder af25ff49211f7a588 als ein Lauf für T2–T4: HANDOFF wegen Bestandstest, danach test-writer a87a77a824a7ad3c3 behoben). Paket-Suite 721 grün. Review am Ende des Stapels.

Offener Punkt af25ff49211f7a588 (Bestandstest Randfall 8 rot wegen `--step 1`): AK-fremd, Test fachlich falsch → test-writer a87a77a824a7ad3c3 angepasst (migrate:rollback --path)
Abweichung af25ff49211f7a588: keine (Fehlalarm: Abweichungen-Zeile des Berichts lautet „keine“)
Annahme im Code: keine
Echtlauf: Vue-Komponente per vue-template-compiler kompiliert und gerendert (Vue 2.7): Standard ohne Website-Feld, Nachricht required; feldWebsite → Feld `Website` (type text, inputmode url, maxlength 2048, ohne required); nachrichtPflicht=false → Textarea ohne required, Label „Ihre Nachricht (optional)“. Layout: entfällt (keine Messung nötig, keine neue Seite).
Review 0/2: 2 muss, 1 sollte, 0 kann — nicht freigegeben (Reviewer acc78edd462b7acf5). Behoben: AK12 README-Satz zur eigenen Kopie der Komponente (Test geschärft a9ce2301312202bc1, README a936b4d52b67f8ca7); Risiko-Fund übernommen (Spec-Kopfzeile `kritisch`, kein Code betroffen). sollte → Task #1325, Altlast → #1327. Nachprüfung folgt.
