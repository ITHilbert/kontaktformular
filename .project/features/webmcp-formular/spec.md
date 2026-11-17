# Feature: WebMCP-Anmerkungen im Kontaktformular (KI-Agenten)

Dashboard-Task #1425 (kontaktformular), Ursprung #1418. Risiko: normal — nur Attribute und eine Browser-Prüfung in der Vue-Komponente; Server-Verhalten unverändert. Spec selbst freigegeben (automode 2026-11-17-0939).

## Ausgangslage
PageSpeed Insights zeigt die Kategorie „Agentic Browsing“ (Lighthouse-Audits `webmcp-registered-tools`, `webmcp-form-coverage`, `webmcp-schema-validity`). WebMCP (Entwurf, Chrome) macht ein `<form>` mit `toolname` und `tooldescription` zu einem Tool für KI-Agenten; das Schema entsteht aus den Formularfeldern. Nur deklarative Attribute, keine JS-Polyfills (der Entwurf ändert Namen noch).

Messung am Prototyp (Lighthouse 13.6.0, Chrome 156, 2026-11-17): Hidden-Felder (`_token`, `site`) und `type=file` erscheinen **nicht** im Schema; der Honeypot `fax` (type text) erscheint mit Warnung „Add a description“; die Checkbox `datenverarbeitung` erscheint als `boolean` und, wegen `required`, als Pflichtfeld des Schemas — ein Agent müsste sie also setzen.

## Akzeptanzkriterien
- **AK1 — Tool registriert:** Gegeben die Komponente `src/Resources/Vue/kontaktformular.vue` — Dann trägt ihr `<form>` die Attribute `toolname` und `tooldescription` (beide gefüllt), und `toolautosubmit` fehlt. Der Agent füllt nur aus, der Mensch sendet ab.
- **AK2 — Name und Beschreibung einstellbar:** Gegeben die Props `tool-name` und `tool-description` (String) der Komponente — Dann stehen deren Werte im `toolname` bzw. `tooldescription`; ohne Prop oder mit leerem Wert gelten deutsche Standardwerte (Name `kontaktformular_senden`, Beschreibung: nennt die Felder, dass die Person selbst absendet, dass die Zustimmung zur Datenverarbeitung nur die Person gibt und dass `fax` immer leer bleibt). Die Config `kontaktformular.webmcp_name` und `kontaktformular.webmcp_beschreibung` (env `KONTAKTFORMULAR_WEBMCP_NAME`, `KONTAKTFORMULAR_WEBMCP_BESCHREIBUNG`, Standard `null` = Komponenten-Standard) reicht die View `kontaktformular::show` als diese Props durch.
- **AK3 — Jedes sichtbare Feld beschrieben:** Gegeben die Felder `Name`, `Email`, `Telefon`, `Website` (nur mit `feld-website`), `Nachricht`, `datenverarbeitung` und `fax` — Dann hat jedes ein `toolparamdescription` (ohne `*`-Zeichen, deutsch); `fax` sagt, dass es ein Spamschutz ist und immer leer bleibt. Das Audit `webmcp-schema-validity` meldet keine Warnung.
- **AK4 — Einwilligung nicht im Schema erzwungen:** Gegeben die Checkbox `datenverarbeitung` — Dann hat sie kein HTML-`required` (das Schema führt sie nicht als Pflichtfeld), ihre `toolparamdescription` sagt, dass nur die Person selbst zustimmt und der Agent sie leer lässt. Die Pflicht bleibt erhalten: Beim Absenden ohne Haken bricht die Komponente ab, zeigt die Browser-Meldung („Bitte stimmen Sie der Verarbeitung Ihrer Daten zu.“ per `setCustomValidity`/`reportValidity`) und sendet nicht; ein Klick auf die Checkbox nimmt die Meldung zurück. Der Server verlangt sie weiter (`required|accepted`).
- **AK5 — Nachweis mit Lighthouse:** Gegeben die gerenderte Komponente in Chrome mit WebMCP — Dann haben die Audits `webmcp-registered-tools`, `webmcp-form-coverage` und `webmcp-schema-validity` Score 1 ohne Warnung; das Schema enthält `Name`, `Email`, `Telefon`, `Nachricht`, `datenverarbeitung` (nicht `_token`, `site`, `Datei`), Pflicht nur `Name` und `Email` (`Nachricht` je nach `nachricht-pflicht`).
- **AK6 — Dokumentiert:** Die README nennt die Config-Schlüssel samt env-Namen und Standards, die Props, dass kein `toolautosubmit` gesetzt ist, die Wahl für Honeypot und Einwilligung samt Restrisiko (Randfall 1), dass Hosts mit eigener Komponentenkopie die Attribute selbst einbauen und neu bauen müssen, und die Config-Datei trägt die neuen Schlüssel.

## Randfälle
1. **Honeypot im Schema (Restrisiko):** `fax` bleibt im Schema, weil jede Variante, die es aus dem Schema nimmt (`disabled`, `form` auf ein fremdes Formular), es auch aus dem Absenden nimmt und den Spamschutz abschaltet (gemessen: Schema ohne `fax`). Gewählt: `toolparamdescription` „immer leer lassen“. Füllt ein Agent es trotzdem, geht die Anfrage wie bisher auf die Bot-Danke-Seite.
2. **Datei:** `type=file` fehlt im Schema; ein Agent kann nichts anhängen (Mensch ergänzt vor dem Absenden).
3. **Herkunft „KI-Agent“** (optional im Task, Punkt 6): entfällt — ohne `toolautosubmit` sendet der Mensch ab, `SubmitEvent.agentInvoked` ist dann nie wahr.

## Nicht-Ziele
`toolautosubmit`, JS-Registrierung (`document.modelContext`), Änderungen am Server-Verhalten, neue Migration.
