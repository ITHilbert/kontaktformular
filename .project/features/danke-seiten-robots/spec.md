# Bugfix: Danke-Seiten ohne Host-View `header.robots_disallow`

## Ausgangslage
Die Danke-Seiten `danke_formular` und `danke_bot_formular` binden in `@section('meta')` die Host-View `header.robots_disallow` ein. Sie ist in der README nicht als Pflicht des Hosts genannt (dort nur `layouts.master`/`layouts.masterBot`) und existiert nur in den Homepage-Projekten. Fehlt sie (z. B. Host packages_dev), antwortet die Seite mit 500 „View [header.robots_disallow] not found“ (`laravel.log` packages_dev, 2026-10-26).
Anlass: Backlog-Eintrag in `../index.md`; Akzeptanzkriterium vom User freigegeben am 2026-10-27.

## Akzeptanzkriterien
- **AK1 — Danke-Seiten ohne Host-View:** Gegeben ein Host ohne View `header.robots_disallow`, wenn `danke-formular` oder `danke-bot-formular` aufgerufen wird, dann antwortet die Seite mit 200 und enthält genau ein `<meta name="robots" content="noindex, nofollow">`.

## Randfälle
- **Randfall 1:** Host mit View `header.robots_disallow` (Homepage-Projekte) → die Danke-Seiten binden sie nicht mehr ein; es gilt AK1 unverändert.

## Nicht-Ziele
- Die Host-Views `header.robots_disallow` in den Homepage-Projekten anpassen oder entfernen (werden dort ggf. von anderen Seiten genutzt).
- Robots-Tags, die das Host-Layout selbst ausgibt (z. B. `layouts.root` in packages_dev mit `index, follow`), unterdrücken.
- Release/Tag: nur auf Ansage des Users.

## Entscheidungsspielraum
- Ob das Meta-Tag direkt in den beiden Views oder in einem gemeinsamen Paket-Partial (`kontaktformular::…`) steht, entscheidet der Coder.
- Keine neuen Pakete.
