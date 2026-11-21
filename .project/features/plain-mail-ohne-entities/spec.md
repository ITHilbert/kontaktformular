# Text-Mail ohne HTML-Entities (kontaktformular, #688)

Typ: Bugfix, Spec-Lücke (Task #688)
Risiko: normal
Entscheidung: selbst freigegeben (automode, 2026-11-21)

## Ziel
Der Text-Teil der Anfrage-Mail (`mail/anfrage_plain.blade.php`) ist Klartext. Er gibt Werte unescaped aus, damit `&` nicht als `&amp;` erscheint (Zeile `Site:` mit Query-String, Name, Mitteilung, Referrer). Der HTML-Teil bleibt escaped.

## Akzeptanzkriterien
- **AK1** Gegeben eine Anfrage mit `url` `http://x.test/kontakt?utm_source=a&utm_medium=b`, wenn der Text-Teil der Mail gerendert wird, dann enthält er die Zeile `Site: http://x.test/kontakt?utm_source=a&utm_medium=b` und kein `&amp;`.
- **AK2** Gegeben eine Anfrage mit Name `Müller & Söhne` und Mitteilung `a < b & "c"`, wenn der Text-Teil gerendert wird, dann stehen Name und Mitteilung unverändert im Text (ohne `&amp;`, `&lt;`, `&quot;`).
- **AK3** Gegeben dieselbe Anfrage, wenn der HTML-Teil gerendert wird, dann steht der Name weiterhin escaped (`Müller &amp; Söhne`) und `<` der Mitteilung als `&lt;` (kein HTML-Einschleusen).

## Randfälle
- Herkunftszeilen (`Referrer` mit `&`) im Text-Teil ebenfalls unescaped.

## Nicht-Ziele
- Keine Änderung am HTML-Teil, keine Änderung an Betreff oder Empfängern.

## Entscheidungsspielraum
- `{!! !!}` in der Plain-View. Keine neuen Pakete.
