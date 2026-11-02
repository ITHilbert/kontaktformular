# 0004 — Anfrage-Mail synchron versenden, Fehler abfangen und melden

Status: gilt · Datum: 2026-11-02 · Task: Dashboard #316

## Kontext
Der Versand der Anfrage-Mail lief nach dem Speichern synchron; ein Fehler (SMTP, ungültige BCC-Adresse) ergab ein 500, obwohl die Anfrage schon gespeichert war. Besucher schickten erneut ab → doppelte Anfragen und Anhänge.

## Entscheidung
Der Versand bleibt synchron. Jede Ausnahme beim Aufbau und Versand der Mail wird abgefangen, in `AnfrageMailNichtVersendet` (Kontext `id`, `nummer`, Ursache als `previous`) übersetzt und per `report()` gemeldet — genau ein Log-Eintrag ohne Besucherdaten. Der Besucher sieht die Danke-Seite. Fehler beim Speichern bleiben ungefangen (500), denn dann ist nichts gespeichert.

## Begründung
Das Paket läuft in Hosts ohne Queue-Worker; eine Mail per `ShouldQueue` käme dort nie an, beim `sync`-Treiber käme dieselbe Ausnahme zurück. Abfangen und Melden schützt vor Duplikaten, ohne Infrastruktur vorauszusetzen.

## Verworfen
- Versand per Queue mit Wiederholungen — setzt einen Worker voraus.
- Fehlerhinweis an den Besucher — er würde erneut absenden, obwohl die Anfrage gespeichert ist.

## Folgen
Der Betreiber erfährt einen Versandfehler nur über das Log bzw. die Fehlerbehandlung des Hosts (z. B. Sentry). Erneuter Versand ist nicht Teil des Pakets.
