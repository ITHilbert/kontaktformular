# Prüfung: herkunft-schalter (kontaktformular, Dashboard #110) — 2026-10-31

Geprüft im Review-Platz `/home/joerg/projekte/.worktrees/packages_dev/reviewer`, Host `fix/herkunft-schalter` (7624af6), Paket kontaktformular `fix/herkunft-schalter` (2d60f58), Diff `main...HEAD` im Paket. Keine `abdeckung.md` vorhanden, die Gliederung folgt den Aussagen der Spec.

Echtlauf: zwei eigene Server (`php -S` aus `public/`, Ports 8197 und 8198, `MAIL_MAILER=log`). 8197 lief mit `KONTAKTFORMULAR_HERKUNFT_ERFASSEN=off`, 8198 ohne die Variable. Ablauf: `GET /?utm_source=x&gclid=y` mit `Referer: https://google.com/` (von Hand gesetzt, um einen fremden Einstieg nachzustellen), dann `_token` von `/login` geholt, dann `POST /anfrage`. Das Formular ist auf keiner Host-Seite eingebunden, deshalb lief der POST ohne gerenderte Formularseite. Danach Session-Dateien, `laravel.log` (Mail im Log) und die DB `packages_dev_review` gelesen, nur lesend per tinker. Server danach beendet.

| AK / Aussage (Zitat) | Ergebnis | Nachweis |
|---|---|---|
| AK1 „Gegeben die Config `kontaktformular.herkunft_erfassen` (env `KONTAKTFORMULAR_HERKUNFT_ERFASSEN`, Standard `true`)“ | bestanden | `src/Config/config.php:21`; Host-App ohne Variable: `config('kontaktformular.herkunft_erfassen')` = `true`. Die Host-Config `config/kontaktformular.php` ist ohne den Schlüssel veröffentlicht, der Wert kommt per `mergeConfigFrom` aus dem Paket |
| AK1 „`false`, `0`, `off`, `no` → aus“ | bestanden | Host-App per tinker (nur lesend) mit `false`/`0`/`off`/`no`/`OFF` ergibt jeweils `false`; Test `test_config_wertet_den_env_wert_wie_filter_var_als_bool` grün, `test_app_config_ist_bei_off_ein_bool_false` grün |
| AK1 „`true`, `1`, `on`, `yes` oder nicht gesetzt → an“ | bestanden | Host-App per tinker: `true`/`1`/`on`/`yes`/nicht gesetzt ergibt `true`; Tests `test_config_ist_ohne_env_wert_an` und `test_app_config_ist_ohne_env_ein_bool_true` grün |
| AK1 „Gecastet wird einmal in der Config.“ | bestanden | Der Cast `filter_var(…, FILTER_VALIDATE_BOOL)` steht nur in `config.php`. Provider (`KontaktformularServiceProvider.php:59`) und Controller (`KontaktformularController.php:111`) lesen nur `config(...)`, ohne eigenen Cast |
| AK2 „Dann steht `HerkunftErfassen` weder in der Router-Gruppe `web`“ | bestanden (Nachweis nur per Sonde und Echtlauf, Test deckt es nicht ab, siehe unten) | Sonde `SondeRouterTest` (Scratchpad) liest den Router direkt nach dem Boot, ohne den Kernel neu aufzulösen. Aus: `gruppen: []`, an: `web: [HerkunftErfassen]`. Echtlauf 8197 (aus): nach `GET /?utm_source=x&gclid=y` keine `kontaktformular_herkunft` in der Session-Datei; 8198 (an) hat den Eintrag. **Testlücke:** `test_aus_router_gruppe_web_enthaelt_die_middleware_nicht` löst vor der Prüfung den Kernel neu auf (`spaetAufgeloesterKernel()`). Dessen Konstruktor überschreibt die Router-Gruppe mit den Kernel-Gruppen. Die Sonde-Mutante beweist das: `HerkunftErfassen` von Hand in den Router gesetzt, danach den Kernel neu aufgelöst, und die Middleware ist aus dem Router verschwunden. Fiele also der Router-Zweig aus dem Schalter heraus (nur der `afterResolving`-Zweig bliebe geschützt), blieben alle Tests grün. Genau dieser Router-Zweig ist aber der Weg in Produktion: `public/index.php` löst den Kernel vor dem Boot auf, also feuert `afterResolving` dort nie |
| AK2 „noch in der Kernel-Gruppe `web`“ | bestanden | `test_aus_kernel_gruppe_web_und_prioritaet_enthalten_die_middleware_nicht_auch_spaet_aufgeloest` grün. Er belegt den Schalter, weil bei an der `afterResolving`-Haken die Middleware in die Kernel-Gruppe setzt (Gegenstück `test_standard_haengt_die_middleware_in_router_und_kernel_ein`) |
| AK2 „noch in der Middleware-Priorität“ | bestanden für die Kernel-Priorität; Router-Priorität nur per Sonde belegt | Kernel-Priorität: Test oben grün. Router-Priorität: `test_aus_router_prioritaet_enthaelt_die_middleware_nicht` hat dieselbe Lücke wie die Router-Gruppe, denn der neu aufgelöste Kernel überschreibt `middlewarePriority` (Sonde-Mutante: danach Kernel-Standardliste ohne `HerkunftErfassen`). Laut Sonde ist die Router-Priorität direkt nach dem Boot bei aus `[]` |
| AK2 „Wenn die App bootet und der HTTP-Kernel (auch erst später) aufgelöst wird“ | bestanden | Später aufgelöster Kernel: per Test (siehe oben). Kernel vor dem Boot aufgelöst (Produktion): per Echtlauf 8197 |
| AK3 „Dann enthält die Session keinen Herkunfts-Eintrag (`HerkunftErfassen::SESSION_KEY`)“ | bestanden | `test_aus_legt_keinen_herkunfts_eintrag_in_der_session_an` grün, mit Gegenprobe: die Session wird geladen. Echtlauf 8197: Session-Datei ohne `kontaktformular_herkunft`, Gegenprobe 8198 mit `{gclid:y, utm_source:x, einstieg:/, referrer:google.com}`. Hinweis: Auch dieser Test läuft in Testbench nur über den Kernel-Zweig (siehe AK2), den Produktionsweg belegt nur der Echtlauf |
| AK3 „die Spalte `herkunft` ist `null`“ | bestanden | `test_aus_speichert_keine_herkunft_in_der_spalte` grün; Echtlauf 8197: Zeile id 2 `herkunft: null`, Gegenprobe 8198 id 3 mit JSON |
| AK3 „und die Mail zeigt `Herkunft: direkt`, `Einstieg: -`, `Referrer: -`, `Klick-ID: -` (wie heute ohne Session)“ | bestanden | `test_aus_mail_zeigt_direkt_und_striche_in_text_und_html` grün (Text- und HTML-Teil); Echtlauf 8197: im Log „Herkunft: direkt / Einstieg: - / Referrer: - / Klick-ID: -“, 8198 „google_ads / / / google.com / gclid“ |
| AK4 „Dann ist `herkunft` `null`“ | bestanden | `test_aus_uebernimmt_vorhandene_session_herkunft_nicht_in_die_spalte` grün, Gegenstück `test_an_uebernimmt_dieselbe_session_herkunft` grün. Echtlauf: die Session von 8198 mit Herkunft (vorher in der Session-Datei nachgewiesen) an 8197 (aus) gepostet, Zeile id 4 `herkunft: null` |
| AK4 „und die Mail zeigt die Werte aus AK3“ | bestanden | `test_aus_zeigt_vorhandene_session_herkunft_nicht_in_der_mail` grün; Echtlauf id 4: „Herkunft: direkt / Einstieg: - / Referrer: - / Klick-ID: -“ |
| AK5 „Dann werden sie beim Speichern entfernt wie in `anfrage-herkunft` AK6“ | bestanden | `test_aus_entfernt_klick_ids_aus_site` (gclid, gbraid, wbraid, alle drei) grün. Der Test schützt nur vor Regressionen, das Verhalten ist unabhängig vom Schalter (`SiteAdresse::ohneKlickIds` vor dem Schalter). Echtlauf 8197: `site=…/seite?gclid=GEHEIM&x=1` wird gespeichert und gemailt als `…/seite?x=1` |
| AK6 „Dann ist `HerkunftErfassen` eingehängt“ | bestanden | `test_standard_haengt_die_middleware_in_router_und_kernel_ein` grün (direkt nach `StartSession`, Kernel neu aufgelöst); Sonde: Router direkt nach dem Boot `web: [HerkunftErfassen]`; Echtlauf 8198 erfasst |
| AK6 „und das Verhalten entspricht `anfrage-herkunft` AK1–AK9“ | bestanden | `test_standard_erfasst_herkunft_und_zeigt_sie_in_der_mail` grün; `AnfrageHerkunftTest.php` ist im Diff unverändert, der Standardpfad ist bis auf die vorgeschaltete Prüfung im Code unverändert |
| AK6 „deren Tests bleiben grün“ | bestanden | `AnfrageHerkunftTest.php` einzeln 510/510 grün, Suite `Kontaktformular` seriell 604/604 grün. Im parallelen Volllauf ist `test_referrer_mit_eigenem_host_gilt_als_kein_referrer` rot, Ursache ist aber APP_URL, nicht das Feature (siehe Regressionen) |
| AK7 „Die README nennt den Schalter“ | bestanden | README Abschnitt „Herkunftserfassung abschalten“: `kontaktformular.herkunft_erfassen` / `KONTAKTFORMULAR_HERKUNFT_ERFASSEN` |
| AK7 „seinen Standard“ | bestanden | „Standard **an**“ |
| AK7 „die Werte zum Abschalten“ | bestanden | „`false` … ebenso aus sind `0`, `off`, `no` und ein leerer Wert“ |
| AK7 „und dass die Mail-Zeilen dann `direkt`/`-` zeigen“ | bestanden | „Die Mail zeigt dann `Herkunft: direkt`, `Einstieg: -`, `Referrer: -` und `Klick-ID: -`.“ |
| Randfall „`KONTAKTFORMULAR_HERKUNFT_ERFASSEN=` (leer) → aus“ | bestanden | Host-App per tinker mit leerem Wert ergibt `false`; Tests `test_app_config_ist_bei_leerem_env_ein_bool_false`, `test_leer_gesetzt_haengt_die_middleware_ebenfalls_nirgends_ein` (Router-Teil mit derselben Lücke wie AK2) und `test_leer_gesetzt_erfasst_ebenfalls_nichts` grün |
| Randfall „Der Schalter wird zur Laufzeit per `config([...])` nach dem Boot geändert → nicht unterstützt; maßgeblich ist der Wert beim Boot für die Middleware.“ | bestanden | Sonde `SondeLaufzeitTest`: Boot mit an, danach `config([... => false])`, dann GET mit UTM. Die Session enthält trotzdem die Herkunft, die Middleware liest den Schalter also nicht selbst. README nennt die Einschränkung |
| Randfall „Der Controller liest den Wert pro Anfrage.“ | bestanden | `test_controller_liest_den_schalter_pro_anfrage` grün: dieselbe Session, erst an (gespeichert), nach `config(false)` → `null` und Mail mit `direkt`/`-` |
| Nicht-Ziel „Mail-Zeilen bei abgeschalteter Erfassung weglassen oder „unbekannt“ schreiben“ | eingehalten | Die Mail-Views sind im Diff unverändert, die vier Zeilen erscheinen auch bei aus (Echtlauf) |
| Nicht-Ziel „Umbau der Umgehung in excel-programmierer.com“, „Release/Tag“ | eingehalten | Im Diff nur Paketdateien, kein Tag im Paket-Repo auf dem Branch |
| Entscheidungsspielraum „Keine neuen Pakete, keine Migration.“ | eingehalten | Keine Änderung an `composer.json`, keine Migration im Diff |

## Sicherheit
- Der Diff besteht aus zwei vorgeschalteten Prüfungen (Provider und Controller) und einem Config-Schlüssel. Er bringt keine neue Eingabe, keine neue Route und kein Mass-Assignment. An der Ausgabe ändert sich nichts.
- Ungültige Env-Werte (z. B. `abc`) ergeben über `filter_var` ohne `NULL_ON_FAILURE` „aus“. Für einen Datenschutzschalter ist das die sichere Richtung und entspricht der Spec („Wertung von `filter_var`“).
- Bei aus wird eine alte Session-Herkunft nicht übernommen, sie bleibt aber in der Session liegen, bis diese abläuft. Die Spec verlangt nur, dass sie „nicht übernommen“ wird, das ist erfüllt.

## Regressionen
- `php artisan test --parallel --compact` (voller Lauf, zweimal): 1242 Tests, 1 Fehlschlag in beiden Läufen: `ITHilbert\Kontaktformular\Tests\Feature\AnfrageHerkunftTest::test_referrer_mit_eigenem_host_gilt_als_kein_referrer` (erwartet `direkt`, erhalten `organisch/sonstige`).
- Ursache ist APP_URL, nicht das Feature. Der Test setzt den Referer fest auf `http://localhost/…`. Die parallelen Worker laden `.env.testing` des Platzes mit `APP_URL=http://127.0.0.1:8102`, der eigene Host der Anfrage ist dort also `127.0.0.1`, und der Referer zählt als fremd. Gegenproben:
  - `APP_URL=http://localhost php artisan test --parallel --compact` gibt OK, 1242 Tests, 12082 Assertions.
  - Die Datei allein und die Suite `Kontaktformular` seriell sind grün.
  - Testdatei und Middleware sind im Diff unverändert.
  - Behoben ist das in Paket-Commit b5df010 (#315), auf `nachtschicht/2026-10-31`, nicht auf diesem Branch.
- `HerkunftSchalterTest.php`: 33/33 grün. Pint `--test` auf die geänderten Dateien: bestanden.
- `laravel.log` während des Echtlaufs: nur `Social login aktiviert, aber Anbieter nicht konfiguriert` (userauth, beim Aufruf von `/login`, kommt aus der Konfiguration des Platzes und gehört nicht zum Feature). Keine Fehler aus kontaktformular.

## Neue Tasks: keine
Der APP_URL-Befund ist #315 und schon behoben. Die Testlücke zu AK2 gehört zur Spec und steht deshalb hier, nicht als Task.

## Fazit: nicht freigegeben
Das Verhalten erfüllt jedes AK und jeden Randfall, belegt durch eigene Sonden und den Echtlauf.

Nicht freigegeben, weil der Produktionsweg von AK2 („Router-Gruppe `web`“, Router-„Middleware-Priorität“) und damit auch die Session-Aussage von AK3 durch keinen Test abgesichert ist, der ohne den Schalter rot würde. Alle Router-Tests lösen vorher den Kernel neu auf, und der überschreibt den Router (Sonde-Mutante). Ein Umbau, der nur den Router-Zweig aus dem Schalter nimmt, bliebe grün, und in Produktion würde die Herkunft wieder in der Session erfasst. Gerade diesen Weg nutzt excel-programmierer.com aus DSGVO-Gründen.

Nötig ist ein Test (test-writer), der bei aus den Router direkt nach dem Boot prüft, ohne den Kernel neu aufzulösen. Gegenprobe im selben Test: `! $this->app->resolved(HttpKernelContract::class)`; bei an zeigt die Sonde, dass `web` dort `[HerkunftErfassen]` enthält. Alternativ einen Kernel nachstellen, der vor dem Boot aufgelöst wird. Am Produktivcode ist nichts zu ändern.

---

# Nachprüfung: herkunft-schalter (kontaktformular, Dashboard #110) — 2026-10-31 (Nachprüfung seit Paket c27d59d)

Geprüft im Review-Platz `/home/joerg/projekte/.worktrees/packages_dev/reviewer`, Host `fix/herkunft-schalter-2` (5e2f5ef), Paket kontaktformular `fix/herkunft-schalter` (06bb424). Die Spec ist seit der ersten Prüfung unverändert. Der Fix besteht nur aus Tests (06bb424, +24 Zeilen in `tests/Feature/HerkunftSchalterTest.php`), der Produktivcode ist unverändert. Deshalb gibt es kein volles Review. Host: nur der Submodul-Stand (da54088, 5e2f5ef).

Mutanten-Methode: Mutierte Kopien von `KontaktformularServiceProvider` liegen im Scratchpad (`…/scratchpad/mutant110/A.php`, `B.php`, `C.php`). Eine eigene Bootstrap-Datei lädt sie per vorangestelltem Autoloader vor dem Composer-Autoloader. Aufruf: `MUTANTE=<x> php vendor/bin/phpunit -c phpunit.xml --bootstrap …/mutant110/bootstrap.php packages/kontaktformular/tests/Feature/HerkunftSchalterTest.php`. Der Platz bleibt dabei unverändert.
- **A:** Der ganze Router-Zweig (Gruppe und Priorität) steht vor dem Schalter, nur `afterResolving` ist geschützt. Das ist der Umbau aus der ersten Prüfung.
- **B:** Nur die Router-Priorität steht vor dem Schalter.
- **C:** Das Einhängen in die Router-Gruppe ist entfernt.

| AK / Aussage (Zitat) | Ergebnis | Nachweis |
|---|---|---|
| AK2 „Dann steht `HerkunftErfassen` weder in der Router-Gruppe `web`“ | bestanden, jetzt durch einen Test abgesichert | Neuer Test `test_aus_router_direkt_nach_dem_boot_ohne_kernel_enthaelt_die_middleware_nicht` grün. Er belegt den Router-Zweig, ohne den Kernel neu aufzulösen: `assertFalse($this->app->resolved(HttpKernelContract::class))` steht vor und nach der Prüfung, und `config(...) === false` ist im Test geprüft. **Mutante A:** genau dieser Test wird rot („Failed asserting that an array does not contain …HerkunftErfassen“), 34 weitere grün. Ohne Mutante: 35/35 grün. Gegenprobe im Gegenstück `test_an_router_direkt_nach_dem_boot_ohne_kernel_enthaelt_die_middleware` (bei an steht `HerkunftErfassen` in der Router-Gruppe): wird bei **Mutante C** rot, die Prüfung läuft also nicht ins Leere. Produktionsweg nachgestellt (`…/mutant110/prod.php`, Host-App wie `public/index.php`: Kernel vor dem Boot, dann `bootstrap()`, nur lesend). Original bei `off`: `router_web: false`, ohne Variable `true`. Mutante A bei `off`: `router_web: true` |
| AK2 „noch in der Middleware-Priorität“ (Router-Priorität) | nicht bestanden (Testlücke besteht weiter; Verhalten korrekt) | Die neue Zeile `assertNotContains(HerkunftErfassen::class, $router->middlewarePriority)` kann in Testbench nicht rot werden. Vor dem Auflösen des Kernels ist `Router::$middlewarePriority` dort `[]` (Standardwert in `Router.php:101`, Sonde der ersten Prüfung). Ohne `StartSession` als Anker gibt `prioritaetMitHerkunft()` die Liste unverändert zurück. **Mutante B:** HerkunftSchalterTest 35/35 grün, die Lücke ist damit belegt. In Produktion ist die Router-Priorität schon vor dem Boot vom Kernel befüllt (`prod.php`: `router_prio_hat_StartSession: true`). Mutante B setzt dort bei `off` die Middleware in die Router-Priorität (`router_prio: true`), das Original nicht (`router_prio: false`). Das Gegenstück bei an prüft die Priorität nicht, und dort wäre sie in Testbench ebenfalls leer. Die Lücke lässt sich schließen, das hat die Sonde `…/mutant110/SondeKernelVorBootTest.php` gezeigt: Mit `#[DefineEnvironment('kernelVorDemBoot')]` (`$app->make(HttpKernelContract::class)` vor dem Boot, wie `public/index.php`) enthält die Router-Priorität `StartSession`. Der Test ist dann im Original grün (an: Priorität enthält die Middleware, aus: nicht) und wird bei Mutante B rot |
| AK2 „noch in der Middleware-Priorität“ (Kernel-Priorität) | bestanden (Prüfung c27d59d) | unverändert, Test weiter grün |
| AK2 „noch in der Kernel-Gruppe `web`“ | bestanden (Prüfung c27d59d) | unverändert, Test weiter grün |
| AK2 „Wenn die App bootet und der HTTP-Kernel (auch erst später) aufgelöst wird“ | bestanden | Später aufgelöster Kernel: bestehende Tests. Kernel vor dem Boot: nachgestellter Produktionsweg `prod.php` (Original, `off`: Router-Gruppe, Router-Priorität, Kernel-Gruppe und Kernel-Priorität alle ohne `HerkunftErfassen`). Durch einen Test abgesichert ist nur die Router-Gruppe, siehe oben |
| AK6 „Dann ist `HerkunftErfassen` eingehängt“ | bestanden | Zusätzlich zur ersten Prüfung: neues Gegenstück `test_an_router_direkt_nach_dem_boot_ohne_kernel_enthaelt_die_middleware` grün und bei Mutante C rot; `prod.php` ohne Variable: `router_web: true`, `router_prio: true` |
| AK3 „Dann enthält die Session keinen Herkunfts-Eintrag (`HerkunftErfassen::SESSION_KEY`)“ | bestanden (Prüfung c27d59d) | Produktivcode unverändert. Der Hinweis aus der ersten Prüfung gilt weiter: Der Session-Test läuft in Testbench über den Kernel-Zweig. Den Produktionsweg belegt der Echtlauf der ersten Prüfung, und die Ursache (Router-Gruppe) ist jetzt per Test abgesichert |
| Randfall „`KONTAKTFORMULAR_HERKUNFT_ERFASSEN=` (leer) → aus“ | bestanden (Prüfung c27d59d) | Der neue Router-Test läuft nur mit `off`. Ein leerer Wert führt im Code über denselben Weg (Config-Cast → `false`, eine Prüfung im Provider). Der Cast ist durch `test_app_config_ist_bei_leerem_env_ein_bool_false` abgesichert |
| Alle übrigen Aussagen (AK1, AK3 Spalte/Mail, AK4, AK5, AK6 Verhalten/Tests, AK7, Randfall Laufzeit/Controller, Nicht-Ziele, Entscheidungsspielraum) | bestanden / eingehalten (Prüfung c27d59d) | Produktivcode und README sind seit der ersten Prüfung inhaltlich unverändert (Feature-Diff vor und nach dem Merge verglichen, siehe Config) |

### Config nach dem Merge (`src/Config/config.php`)
- Die Konfliktlösung in c27d59d vereinigt beide Seiten korrekt. Gegenüber `nachtschicht/2026-10-31` (c27d59d^2) kommt nur der Block `herkunft_erfassen` dazu (3 Zeilen). Gegenüber dem alten Feature-Stand (c27d59d^1) kommen nur `disk` (#119) und `loeschen_einplanen` dazu. Es fehlt kein Schlüssel, und es gibt keinen doppelten.
- `herkunft_erfassen` ist weiterhin der einzige Cast (`filter_var(…, FILTER_VALIDATE_BOOL)`), wie AK1 verlangt.
- Der Feature-Diff gegen die Basis ist inhaltlich derselbe wie in der ersten Prüfung, nur mit verschobenen Zeilennummern: Config +3, Controller +4, Provider +4, README +9. Die Prüfung im Controller steht unverändert vor dem Speichern.
- Kosmetik, ohne Auswirkung: Zwischen dem Block `herkunft_erfassen` und dem Kommentar zu `disk` fehlt die Leerzeile, die die übrigen Blöcke trennt.

## Sicherheit (Nachprüfung)
- Der Fix ändert nur Tests, Produktivcode ist nicht betroffen. Die Config-Vereinigung bringt keinen neuen Schlüssel aus Nutzereingaben.

## Regressionen (Nachprüfung)
- `php artisan test --parallel --compact` (voller Lauf, ohne Filter): **OK, 2077 Tests, 21548 Assertions**. Der APP_URL-Fehlschlag der ersten Prüfung ist mit #315 (b5df010, jetzt eingemergt) weg.
- `HerkunftSchalterTest.php` einzeln: 35/35 grün (239 Assertions).
- `laravel.log`: Die einzigen neuen ERROR-Einträge (14:33:09 UTC, `UrlGenerator … null given`) stammen vom ersten Aufruf meiner eigenen Sonde `prod.php`, noch ohne gebundenen Request. Sie kommen nicht aus dem Feature.

## Neue Tasks: keine

## Fazit: nicht freigegeben
Die Kernfrage ist erfüllt. Der neue Test belegt den Router-Zweig direkt nach dem Boot, ohne den Kernel aufzulösen (Gegenprobe `resolved() === false` vor und nach der Prüfung). Er wird bei dem Umbau aus der ersten Prüfung rot (Mutante A: Router-Zweig ganz vor dem Schalter), und sein Gegenstück wird bei Mutante C rot. Für die Router-Gruppe `web`, den DSGVO-relevanten Weg, ist die Lücke geschlossen.

Nicht freigegeben, weil die zweite Router-Aussage aus AK2 („noch in der Middleware-Priorität“, Router-Seite) weiter nur scheinbar getestet ist. Die neue `assertNotContains` auf `$router->middlewarePriority` prüft in Testbench eine leere Liste und bleibt grün, wenn nur die Router-Priorität aus dem Schalter fällt (Mutante B). In Produktion stünde die Middleware dann in der Router-Priorität (nachgestellt mit `prod.php`). Die erste Prüfung hatte die Router-Priorität ausdrücklich in die Begründung aufgenommen. Das heutige Verhalten ist korrekt, und eine Middleware in der Priorität ohne Gruppeneintrag wirkt nicht. Es fehlt also nur ein Test, kein Fix.

Nötig (test-writer, Produktivcode bleibt): Ein Testpaar mit `#[DefineEnvironment]`, das den HTTP-Kernel vor dem Boot auflöst wie `public/index.php`. Gegenprobe im Test: `in_array(StartSession::class, $router->middlewarePriority)`, sonst ist die Prüfung wieder leer. Bei aus darf `HerkunftErfassen` in der Router-Priorität nicht stehen, bei an muss sie dort stehen. Die Sonde `/tmp/claude-1000/-home-joerg-projekte-packages-dev/e400a5d4-2284-415e-bf05-a955f4785230/scratchpad/mutant110/SondeKernelVorBootTest.php` zeigt den Aufbau: im Original grün, bei Mutante B rot. Alternativ die leere Prüfung in 06bb424 durch diesen Aufbau ersetzen.
