# Plan: webmcp-formular

Branch: `feature/webmcp-formular` (Paket-Repo kontaktformular) · Spec: `spec.md` · Dashboard-Task #1425
Spec und Plan selbst freigegeben (automode 2026-11-17-0939).

## Aufgaben
- [x] T1 (AK1–AK4, AK6) test-writer — Tests: `tests/js/webmcpFormular.test.mjs` (Vue-Template kompilieren und rendern), `tests/Feature/WebmcpFormularTest.php` (Config, View, README)
- [x] T2 (AK1–AK4, AK6) [pro] Komponente, Config, View, README — Tests: `tests/js/webmcpFormular.test.mjs`, `tests/Feature/WebmcpFormularTest.php`
- [x] T3 NACHWEIS (Moderator): GATE-Log, Tests, Lighthouse (AK5)

Bestehende Tests: `tests/Feature/KontaktformularTest.php`, `tests/Feature/AnfrageWebsiteFeldTest.php`, `tests/js/tokenAuffrischen.test.mjs`

## Stand
T1–T3 erledigt (test-writer a5c9a735a7e81c73b, coder-pro a1c66f8a31377acec, GATE GRÜN 09:54). Paket-Suite 728 grün, JS 34 pass.

Offener Punkt a1c66f8a31377acec: AK-fremd (Checkbox ohne required, Pflicht per JS in absenden — ist AK4 selbst; Server verlangt sie weiter)
Abweichung a1c66f8a31377acec: keine (Fehlalarm: Abweichungen-Zeile des Berichts lautet „keine“)
Annahme im Code: keine
Echtlauf: browser: Komponente (Vue 2.7, vue-template-compiler) in Chrome 156 gerendert, Lighthouse 13.6.0 agentic-browsing: webmcp-registered-tools 1, webmcp-form-coverage 1, webmcp-schema-validity 1 (AK5); Tool kontaktformular_senden mit Beschreibung registriert. Verhalten der Checkbox-Meldung per node-Test (Attrappen).
Layout: entfällt (keine Darstellungsänderung)
Review: entfällt (Risiko normal, kein Server-Verhalten, kein neuer Endpunkt, rund 40 Zeilen Produktivcode, kein Sicherheitsbezug)
