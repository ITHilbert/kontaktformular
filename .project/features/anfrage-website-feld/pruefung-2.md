# Prüfung: anfrage-website-feld — 2026-11-16 (Nachprüfung seit e716610)

Geprüft: Paket `kontaktformular`, Fix `94ecead..e716610` (ein Commit). Bisherige Prüfung: `pruefung.md` (Stand 94ecead, 2 muss, 1 sollte).

Die Spec hat sich seit der Prüfung geändert, aber nur in der Kopfzeile (`Risiko: normal` → `Risiko: kritisch` mit Begründung). Genau das verlangte der muss-Fund. AKs, Randfälle und Nicht-Ziele sind wörtlich gleich. Der Fix baut keinen Produktivcode um. Deshalb reicht eine Nachprüfung, kein volles Review.

| AK / Aussage (Zitat) | Ergebnis | Nachweis |
|---|---|---|
| AK12 „den Hinweis „Hosts mit eigener Kopie der Komponente bauen das Feld selbst ein““ | bestanden | README (Abschnitt „Optionale Felder“): „Hosts mit eigener Kopie der Komponente (`kontaktformular.vue`) bauen das Feld bzw. die Props `feld-website` und `nachricht-pflicht` selbst ein.“ Das ist der Wortlaut der Spec, ergänzt um Dateinamen und Props. Test `test_readme_dokumentiert_beide_schluessel_props_spalte_und_hinweise` grün. Er prüft jetzt per Regex „eigene[rn] Kopie der Komponente … selbst“, also mit dem Objekt. Gegenprobe: dieselbe Regex trifft in der README von 94ecead nicht („eigene Kopie der View“), in der von e716610 schon. Der Test wäre auf dem alten Stand also rot gewesen |
| Risiko „Risiko: kritisch“ (Spec-Kopfzeile) | bestanden | `spec.md` Zeile 3: „Risiko: kritisch — Kundendaten (neue personenbezogene Angabe, gespeichert und per Mail versendet)“. Die Fehlerpfad-Prüfung, die bei kritisch nötig ist, steht schon in `pruefung.md` unter Sicherheit, ohne Befund: Bei Mailfehler wird `website` nicht geloggt, bei Validierungsfehler entsteht kein Datensatz. Der Fix berührt diesen Code nicht |
| AK12 übrige Aussagen (Schlüssel, env, Standards, Props, Spalte/Migration, Bot-Heuristik, Datenschutzerklärung) | bestanden (Prüfung e716610) | Der README-Diff ändert nur die eine Zeile. Die übrigen Begriffe prüft derselbe Test weiter. Nur `eigene Kopie` ist aus der Liste entfallen, weil die Regex das genauer prüft |
| AK1–AK11 | bestanden (Prüfung e716610) | Fix ohne Änderung an `src/`, Config, Migrationen, Views, Vue |
| Randfälle 1–5 | bestanden (Prüfung e716610) | wie oben |
| Nicht-Ziele | eingehalten (Prüfung e716610) | Fix-Diff nur README, Test, Spec, Plan, pruefung.md |

## Sicherheit
- Diff des Fixes: nur README-Satz, Test-Assertion, Spec-Kopfzeile, `plan.md` und `pruefung.md`. `git diff --stat 94ecead..e716610 -- src config database resources` ist leer. Kein sicherheitsrelevanter Code berührt.

## Regressionen
- Paket-Suite einmal im Platz: `cd …/reviewer/packages/kontaktformular && …/vendor/bin/phpunit -c phpunit.xml.dist --bootstrap …/vendor/autoload.php` → **OK (721 tests, 9366 assertions)**, 17,7 s.
- Der Test wurde geschärft (Teilwort durch Regex mit Objekt ersetzt), nicht aufgeweicht. Er bleibt eine Prüfung der README, also reine Doku. Für ein Doku-AK ist das der passende Nachweis.

## Funde: 0 muss, 0 sollte, 0 kann
- Offen aus der ersten Prüfung, schon als Task angelegt: sollte, Nachricht wird bei Standard-Config getrimmt gespeichert (→ #1325). Altlast `Nachricht[]` ergibt 500 (→ #1327).

## Neue Tasks: keine

## Fehlerprotokoll
- keine

## Rechtshinweise
- Unverändert aus `pruefung.md`: Datenschutzerklärung der Hosts bei `feld_website` an. Fester Datenschutz-Link auf it-hilbert.com in `kontaktformular.vue` (Altlast).

## Fazit: freigegeben
Beide muss-Funde sind behoben. Der AK12-Hinweis steht im Wortlaut der Spec in der README, und der Test prüft ihn mit dem Objekt (Gegenprobe: rot auf 94ecead). Die Spec nennt `Risiko: kritisch` mit Begründung. Die Paket-Suite ist grün.
