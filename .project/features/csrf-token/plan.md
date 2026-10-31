# Plan: csrf-token (Dashboard #120)

Branch: `fix/csrf-token` (Paket kontaktformular, von `main`) · Spec: `spec.md` · Spec selbst freigegeben (Nachtschicht 2026-10-31)

## Aufgaben
- [x] T1 (AK1, AK2, AK5) test-writer (12 rot: Route 404/fehlt; mit Stub-Route alle grün, CSRF im Test scharf): Feature-Tests Token-Route, Absenden mit frischem/altem Token, 419 ohne Sitzung — Tests: `tests/Feature/CsrfTokenRouteTest.php`
- [x] T2 (AK3, AK4) test-writer (24 Tests, rot: ERR_MODULE_NOT_FOUND; Schnittstelle `erzeugeTokenAuffrischer` im Kopf der Testdatei): JS-Tests für `src/Resources/Vue/tokenAuffrischen.mjs` (Fakes für `fetch` und Formular) — Tests: `tests/js/tokenAuffrischen.test.mjs`
- [x] T2b (Randfall 3) test-writer (35f6921): `zuruecksetzen()` nach Zurück aus dem Browser-Cache, vorher rot (`zuruecksetzen is not a function`) — Tests: `tests/js/tokenAuffrischen.test.mjs`
- [x] T3 (AK1–AK6) [pro] Route + Controller-Methode (JSON, `no-store`), Modul `tokenAuffrischen.mjs`, Komponente nutzt es beim Absenden, Prop `token-route` in `show.blade.php`, README — Tests: `tests/Feature/CsrfTokenRouteTest.php`, `tests/js/tokenAuffrischen.test.mjs`
- [x] T4 NACHWEIS (Moderator)
- [ ] T5 REVIEW (reviewer, Review-Platz) → `pruefung.md`

## Stand
T1–T4 erledigt. NACHWEIS: GATE GRÜN (coder-pro, 07:59 und 08:03); vom Moderator erneut: CsrfTokenRouteTest 12, JS 26/26, Testsuite `Kontaktformular` 583 grün, Pint grün. Echter Lauf gegen den Host auf 8004: `GET /kontaktformular/csrf-token` → 200, JSON, `Cache-Control: no-store, private`, gleiches Token in derselben Sitzung, anderes in neuer; Fremd-POST `/anfrage` → 419; `laravel.log` ohne neuen Eintrag. Komponente mit `vue/compiler-sfc` (2.7) kompiliert, Vorlage und Script ohne Fehler (9 Parser-Hinweise zu leeren Tags wie im alten Stand). Im NACHWEIS gefunden: Nach „Zurück“ aus dem bfcache blieb das Absenden gesperrt → Randfall 3 (ba4beb9), Test T2b, Nachtrag Coder. Nicht geprüft: Browser-Lauf der Komponente im Bundle eines Hosts (it-hilbert.com, beim Rollout). Weiter: T5 REVIEW.
