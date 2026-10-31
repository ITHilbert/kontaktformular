# Plan: csrf-token (Dashboard #120)

Branch: `fix/csrf-token` (Paket kontaktformular, von `main`) · Spec: `spec.md` · Spec selbst freigegeben (Nachtschicht 2026-10-31)

## Aufgaben
- [x] T1 (AK1, AK2, AK5) test-writer (12 rot: Route 404/fehlt; mit Stub-Route alle grün, CSRF im Test scharf): Feature-Tests Token-Route, Absenden mit frischem/altem Token, 419 ohne Sitzung — Tests: `tests/Feature/CsrfTokenRouteTest.php`
- [x] T2 (AK3, AK4) test-writer (24 Tests, rot: ERR_MODULE_NOT_FOUND; Schnittstelle `erzeugeTokenAuffrischer` im Kopf der Testdatei): JS-Tests für `src/Resources/Vue/tokenAuffrischen.mjs` (Fakes für `fetch` und Formular) — Tests: `tests/js/tokenAuffrischen.test.mjs`
- [ ] T3 (AK1–AK6) [pro] Route + Controller-Methode (JSON, `no-store`), Modul `tokenAuffrischen.mjs`, Komponente nutzt es beim Absenden, Prop `token-route` in `show.blade.php`, README — Tests: `tests/Feature/CsrfTokenRouteTest.php`, `tests/js/tokenAuffrischen.test.mjs`
- [ ] T4 NACHWEIS (Moderator)
- [ ] T5 REVIEW (reviewer, Review-Platz) → `pruefung.md`

## Stand
T1, T2 erledigt. Weiter: T3 [pro].
