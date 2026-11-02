# Spec: Herkunftserfassung abschaltbar (Dashboard #110)

## Ausgangslage
Die Middleware `HerkunftErfassen` (Feature `anfrage-herkunft`, #85) hängt sich automatisch in die Gruppe `web` ein (`KontaktformularServiceProvider::registriereHerkunftMiddleware`, am Router und per `afterResolving` am HTTP-Kernel). excel-programmierer.com muss sie aus DSGVO-Gründen abgeschaltet lassen und entfernt sie heute von außen an beiden Stellen — das hängt an Paket-Interna.

## Akzeptanzkriterien
- **AK1 — Schalter:** Gegeben die Config `kontaktformular.herkunft_erfassen` (env `KONTAKTFORMULAR_HERKUNFT_ERFASSEN`, Standard `true`) — Wenn der Wert gelesen wird — Dann gilt die Wertung von `filter_var(…, FILTER_VALIDATE_BOOL)`: `false`, `0`, `off`, `no` → aus; `true`, `1`, `on`, `yes` oder nicht gesetzt → an. Gecastet wird einmal in der Config.
- **AK2 — Aus: keine Middleware:** Gegeben `herkunft_erfassen` = aus — Wenn die App bootet und der HTTP-Kernel (auch erst später) aufgelöst wird — Dann steht `HerkunftErfassen` weder in der Router-Gruppe `web` noch in der Kernel-Gruppe `web` noch in der Middleware-Priorität.
- **AK3 — Aus: keine Herkunft gespeichert:** Gegeben `herkunft_erfassen` = aus — Wenn ein Besucher eine Seite mit `?utm_source=x&gclid=y` aufruft und danach eine gültige Anfrage abschickt — Dann enthält die Session keinen Herkunfts-Eintrag (`HerkunftErfassen::SESSION_KEY`), die Spalte `herkunft` ist `null`, und die Mail zeigt `Herkunft: direkt`, `Einstieg: -`, `Referrer: -`, `Klick-ID: -` (wie heute ohne Session).
- **AK4 — Aus: vorhandene Session-Herkunft wird nicht übernommen:** Gegeben `herkunft_erfassen` = aus und eine Session, in der unter `HerkunftErfassen::SESSION_KEY` schon eine Herkunft steht (aus der Zeit vor dem Abschalten) — Wenn eine gültige Anfrage abgeschickt wird — Dann ist `herkunft` `null` und die Mail zeigt die Werte aus AK3.
- **AK5 — Klick-ID aus `site` bleibt entfernt:** Gegeben `herkunft_erfassen` = aus — Wenn der Formularwert `site` `gclid`, `gbraid` oder `wbraid` enthält — Dann werden sie beim Speichern entfernt wie in `anfrage-herkunft` AK6.
- **AK6 — Standard unverändert:** Gegeben `KONTAKTFORMULAR_HERKUNFT_ERFASSEN` nicht gesetzt — Dann ist `HerkunftErfassen` eingehängt und das Verhalten entspricht `anfrage-herkunft` AK1–AK9; deren Tests bleiben grün.
- **AK7 — Dokumentiert:** Die README nennt den Schalter, seinen Standard, die Werte zum Abschalten und dass die Mail-Zeilen dann `direkt`/`-` zeigen.

## Randfälle
- `KONTAKTFORMULAR_HERKUNFT_ERFASSEN=` (leer) → aus (Wertung wie `filter_var`, wie bei `loeschen_einplanen`).
- Der Schalter wird zur Laufzeit per `config([...])` nach dem Boot geändert → nicht unterstützt; maßgeblich ist der Wert beim Boot für die Middleware. Der Controller liest den Wert pro Anfrage.

## Nicht-Ziele
- Mail-Zeilen bei abgeschalteter Erfassung weglassen oder „unbekannt“ schreiben (Format bleibt für den Marketing-Parser stabil).
- Umbau der Umgehung in excel-programmierer.com (eigenes Projekt, nach Release).
- Release/Tag.

## Entscheidungsspielraum
- Keine neuen Pakete, keine Migration.
- Spec selbst freigegeben (Nachtschicht 2026-10-31). Die Abnahme im Task („Mail ohne Herkunftsangaben“) ist als „Zeilen ohne Daten“ gelesen, wie die Task-Beschreibung („Mail „direkt“/„-““).
