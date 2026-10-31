# Spec: CSRF-Token vor dem Absenden auffrischen (Dashboard #120)

## Ausgangslage
`src/Resources/Vue/kontaktformular.vue` liest das Token einmal in `mounted()` aus `<meta name="csrf-token">` und schickt es als `_token` mit. Nach einer abgelaufenen Sitzung liefert das Absenden 419; „Zurück“ holt die Seite aus dem Browser-Cache mit den Eingaben, aber mit dem alten Token → jedes weitere Absenden wieder 419, die Anfrage geht verloren (nachgestellt auf it-hilbert.com, 2026-10-27).

## Akzeptanzkriterien
- **AK1 — Token-Route:** Gegeben die Route `GET /kontaktformular/csrf-token` (Name `kontaktformular.csrf_token`, Gruppe `web`) — Wenn sie aufgerufen wird — Dann antwortet sie mit 200 und JSON `{"token": "<csrf_token() der Sitzung>"}`, und `Cache-Control` enthält `no-store`. Ohne Sitzungs-Cookie entsteht eine neue Sitzung, und das gelieferte Token gehört zu ihr.
- **AK2 — Absenden mit frischem Token angenommen:** Gegeben ein Besucher, dessen Formular noch ein Token einer abgelaufenen Sitzung enthält — Wenn er das Token von der Route aus AK1 holt und `POST /anfrage` mit diesem Token, dem Sitzungs-Cookie dieser Antwort und gültigen Feldern sendet — Dann wird die Anfrage angenommen (Weiterleitung auf `danke_formular`), mit dem alten Token dagegen 419.
- **AK3 — Komponente frischt vor dem Absenden auf:** Gegeben die Vue-Komponente — Wenn der Besucher absendet — Dann ruft sie zuerst die Token-Route auf (gleiche Herkunft, ohne Cache), schreibt das gelieferte Token in das Feld `_token` und sendet erst danach das Formular ab, genau einmal.
- **AK4 — Abruf schlägt fehl:** Gegeben die Token-Route ist nicht erreichbar, antwortet nicht mit 200 oder liefert kein Token (leer, kein String, kein JSON) — Wenn der Besucher absendet — Dann sendet die Komponente das Formular mit dem vorhandenen Token ab (Verhalten wie heute), genau einmal, ohne Fehler im Browser.
- **AK5 — CSRF-Prüfung bleibt scharf:** Gegeben ein `POST /anfrage` ohne Sitzungs-Cookie (Fremdformular) oder mit falschem Token — Dann antwortet die App mit 419; das Paket trägt keine Ausnahme in die CSRF-Prüfung ein.
- **AK6 — Dokumentiert:** Die README nennt die Route, ihren Zweck und dass Hosts, die die Komponente in ihr Bundle bauen, nach dem Update neu bauen müssen.

## Randfälle
- Besucher klickt während des Abrufs erneut auf „Anfrage senden“ → es wird trotzdem nur einmal abgesendet.
- Die Route wird von einer fremden Seite per JavaScript abgefragt → ohne CORS-Freigabe kann sie die Antwort nicht lesen (Same-Origin); das Paket setzt keine CORS-Header.
- Seite kommt per „Zurück“ aus dem Browser-Cache (`pageshow` mit `event.persisted`, JS-Zustand erhalten) → die Sperre gegen Doppelklick wird aufgehoben; das nächste Absenden holt wieder ein frisches Token und sendet genau einmal ab. Das Modul bietet dafür eine Funktion zum Zurücksetzen, die Komponente ruft sie bei `pageshow` mit `persisted` auf. Ohne Zurücksetzen bleibt ein zweites Absenden wirkungslos (Randfall 1). *Ergänzt 2026-10-31 im NACHWEIS (Moderator): Mit erhaltenem JS-Zustand blieb der Knopf nach „Zurück“ ohne Wirkung — genau der Ablauf aus #120.*
- Token-URL: Die Komponente bekommt sie als Prop (`token-route`, gesetzt in `show.blade.php` per `route('kontaktformular.csrf_token')`); fehlt die Prop, gilt `/kontaktformular/csrf-token`.

## Nicht-Ziele
- Token schon bei `pageshow` holen (vor dem Absenden) — das Holen beim Absenden genügt; bei `pageshow` wird nur die Sperre zurückgesetzt (Randfall 3). *Geändert 2026-10-31.*
- Änderungen an der 419-Seite der Hosts.
- Release/Tag, Rollout (it-hilbert.com baut danach neu).

## Entscheidungsspielraum
- Keine neuen Pakete (auch kein npm-Paket). Die Abruf-/Absende-Logik liegt in einem kleinen ES-Modul neben der Komponente (`src/Resources/Vue/tokenAuffrischen.mjs`), damit sie per `node --test` prüfbar ist; die Komponente nutzt es dünn.
- Throttle der Route frei wählbar (z. B. `throttle:60,1`).
- Spec selbst freigegeben (Nachtschicht 2026-10-31). Sicherheit: Die Route schwächt die CSRF-Prüfung nicht (Same-Origin, kein CORS, `no-store`); im Protokoll unter „Zum Prüfen“.
