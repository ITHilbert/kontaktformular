# Spec: kontaktformular – Fehler beim Mailversand nach gespeicherter Anfrage (Dashboard #316)

Spec selbst freigegeben (Nachtschicht 2026-11-02).

## Ausgangslage
`KontaktformularController::anfrage()` speichert die Anfrage (`$kontakt->save()`) und versendet danach synchron `Mail::to(config('kontaktformular.mailTo'))->send(new Anfrage($kontakt))`. Wirft der Versand (z. B. SMTP 535, `TransportException`; ungültige BCC-Adresse, `RfcComplianceException`), sieht der Besucher ein 500, obwohl die Anfrage samt Anhang schon gespeichert ist. Er schickt erneut ab → doppelte Anfragen und Anhänge.

## Akzeptanzkriterien
- **AK1 — Danke-Seite trotz Versandfehler:** Gegeben ein Mailer, der beim Versand der Anfrage-Mail eine Ausnahme wirft (`Symfony\Component\Mailer\Exception\TransportException`) — Wenn ein Besucher ein gültiges Formular absendet — Dann leitet die Antwort auf die Route `danke_formular` weiter (kein 500).
- **AK2 — Genau einmal gespeichert:** Gegeben AK1 — Dann ist die Anfrage genau einmal in der Tabelle gespeichert, mit den Formularwerten; ein Anhang liegt genau einmal in der Ablage.
- **AK3 — Fehler wird gemeldet:** Gegeben AK1 — Dann wird die Ausnahme über Laravels Fehlerbehandlung gemeldet (`report()`, sichtbar im Log mit der Meldung der Ausnahme) und der Log-Eintrag nennt die id bzw. `nummer` der gespeicherten Anfrage.
- **AK4 — Jede Ausnahme beim Versand:** Gegeben ein Mailer, der beim Versand eine andere Ausnahme wirft (z. B. `Symfony\Component\Mime\Exception\RfcComplianceException` bei ungültiger `mailBcc`-Adresse) — Dann gelten AK1–AK3 ebenso.
- **AK5 — Ohne Fehler unverändert:** Gegeben ein funktionierender Mailer — Dann wird die Anfrage-Mail wie bisher genau einmal an `mailTo` (mit BCC laut `mailBcc`) versendet, die Antwort leitet auf `danke_formular` weiter, und es wird nichts als Fehler gemeldet.
- **AK6 — README:** Die README nennt im Abschnitt „Konfiguration“ (bei `mailTo`/`mailBcc`), dass ein Versandfehler den Besucher nicht aufhält, gemeldet wird (Log) und die Anfrage gespeichert bleibt.

## Randfälle
- Fehler vor dem Speichern (Validierung, Bot-Erkennung) → Verhalten unverändert.
- Fehler beim Speichern selbst (DB) → nicht abgefangen, Verhalten unverändert (500), denn dann ist nichts gespeichert.

## Nicht-Ziele
- Versand per Queue (`ShouldQueue`) — Hosts ohne Worker bekämen sonst nie eine Mail.
- Erneuter Versandversuch, Hinweis an den Betreiber außerhalb des Logs, Admin-Ansicht „nicht versendet“.
- Validierung der BCC-Adressen (eigener Punkt unter „Geplant“).
- Release/Tag.

## Entscheidungsspielraum
- Keine neuen Pakete, keine Migration. Ort des Abfangens (Controller oder eigene Action) entscheidet der Coder im Stil des Pakets.
