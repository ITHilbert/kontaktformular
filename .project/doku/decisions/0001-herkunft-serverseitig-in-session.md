# 0001 Herkunft der Anfrage serverseitig in der Session, ohne neue Cookies
Status: gilt
Datum: 2026-10-26

## Entscheidung
Die Herkunft (Klick-IDs `gclid`/`gbraid`/`wbraid`, `utm_*`, Einstiegsseite, Referrer-Host) merkt sich die Middleware `HerkunftErfassen` beim ersten Request einer Session (First Touch) in der vorhandenen, serverseitigen Laravel-Session und speichert sie mit der Anfrage in der Spalte `herkunft`. Keine eigenen Cookies, kein `localStorage`/`sessionStorage`. Beim Session-Treiber `cookie` wird nichts erfasst. In der Mail steht nur der Name der Klick-ID, nie ihr Wert; Klick-IDs werden auch aus `Site:` entfernt.

## Warum
Speichern auf dem Endgerät zur Attribution ist nicht technisch notwendig und bräuchte nach § 25 TDDDG eine Einwilligung. Die Session existiert ohnehin (CSRF); Rechtsgrundlage für die Auswertung ist Art. 6 Abs. 1 lit. f DSGVO, Hinweis in der Datenschutzerklärung nötig. Beim Treiber `cookie` lägen die Session-Daten im Browser. Der Wert der Klick-ID geht per BCC auch ans Lead-Postfach und wird dort nicht gebraucht (Datenminimierung).

## Verworfene Alternativen
- Eigenes Cookie oder `localStorage` mit der `gclid` — einwilligungspflichtig.
- Herkunft nur aus der Seite, auf der das Formular abgeschickt wird — verliert die `gclid` nach dem ersten Seitenwechsel (Ausgangsproblem).
- Last Touch (Werte überschreiben) — die Auswertung braucht den Einstieg über die Anzeige.

Spec: `.project/features/anfrage-herkunft/spec.md`, Dashboard-Task #85.
