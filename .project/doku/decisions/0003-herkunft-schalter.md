# 0003 — Herkunftserfassung per Config-Schalter abschaltbar

Status: gilt · Datum: 2026-11-02 · Task: Dashboard #110 · Ergänzt: [0002](0002-middleware-registrierung-web.md)

## Kontext
Die Herkunftserfassung (0001) schaltet sich in jedem Host ein, der Session und `web`-Middleware nutzt. excel-programmierer.com darf sie aus Datenschutzgründen (Marketing-Attribution ohne Einwilligung bzw. ohne Hinweis in der Datenschutzerklärung) nicht nutzen und entfernte die Middleware bisher von außen — an Router und Kernel, also an Interna des Pakets (0002).

## Entscheidung
Config-Schalter `kontaktformular.herkunft_erfassen` (env `KONTAKTFORMULAR_HERKUNFT_ERFASSEN`, Standard an). Gecastet wird einmal in der Config (`filter_var(…, FILTER_VALIDATE_BOOL)`; ungültige Werte ergeben „aus“). Bei aus hängt der Provider `HerkunftErfassen` an keiner der Stellen aus 0002 ein (Router-Gruppe, Router-Priorität, Kernel per `afterResolving`), und der Controller übernimmt keine Session-Herkunft. Maßgeblich für die Middleware ist der Wert beim Boot.

## Begründung
Ein Schalter ist die stabile Schnittstelle; das Entfernen von außen bricht, sobald das Paket anders einhängt. Ungültige Werte ergeben aus — für einen Datenschutzschalter die sichere Richtung.

## Verworfen
- Middleware nur per Host-Code entfernen lassen (Stand vorher) — hängt an Paket-Interna.
- Prüfung in der Middleware selbst — sie bliebe eingehängt; der Controller muss die Herkunft ohnehin unabhängig prüfen.

## Folgen
Tests müssen beide Kernel-Wege abdecken (Kernel vor dem Boot wie `public/index.php`, Kernel nach dem Boot wie Testbench) — siehe `pruefung.md` von herkunft-schalter.
