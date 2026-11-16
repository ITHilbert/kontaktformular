AK1 | "Config-Schlüssel und Standards" | tests/Feature/AnfrageWebsiteFeldTest.php::test_config_standards_und_auswertung_der_umgebungsvariablen
AK2 | "Website wird gespeichert" | tests/Feature/AnfrageWebsiteFeldTest.php::test_website_wird_in_spalte_website_gespeichert, tests/Feature/AnfrageWebsiteFeldTest.php::test_migration_legt_nullbare_spalte_website_an
AK3 | "Website bleibt optional und begrenzt" | tests/Feature/AnfrageWebsiteFeldTest.php::test_website_fehlt_oder_ist_leer_wird_normal_gespeichert_mit_null, tests/Feature/AnfrageWebsiteFeldTest.php::test_website_ueber_2048_zeichen_wird_abgelehnt
AK4 | "Website in der Mail" | tests/Feature/AnfrageWebsiteFeldTest.php::test_mail_zeigt_website_direkt_nach_telefon_und_vor_mitteilung_in_html_und_text, tests/Feature/AnfrageWebsiteFeldTest.php::test_website_steht_ohne_telefon_direkt_nach_der_email_und_ist_im_html_maskiert, tests/Feature/AnfrageWebsiteFeldTest.php::test_mail_ohne_website_hat_keine_zeile_website
AK5 | "Zeilenumbrüche neutralisiert" | tests/Feature/AnfrageWebsiteFeldTest.php::test_zeilenumbrueche_in_website_werden_je_durch_ein_leerzeichen_ersetzt
AK6 | "Standard feld_website=false wie bisher" | tests/Feature/AnfrageWebsiteFeldTest.php::test_ohne_feld_website_funktioniert_die_anfrage_auf_tabelle_ohne_spalte, tests/Feature/AnfrageWebsiteFeldTest.php::test_ohne_feld_website_wird_der_wert_auch_mit_vorhandener_spalte_nicht_gespeichert
AK7 | "Standard nachricht_pflicht=true wie bisher" | tests/Feature/AnfrageWebsiteFeldTest.php::test_nachricht_pflicht_standard_leitet_ohne_nachricht_zur_bot_seite
AK8 | "Nachricht als Kann-Angabe" | tests/Feature/AnfrageWebsiteFeldTest.php::test_ohne_nachricht_wird_gespeichert_verschickt_und_mitteilung_fehlt_in_der_mail
AK9 | "Gefüllte Nachricht wird weiter geprüft" | tests/Feature/AnfrageWebsiteFeldTest.php::test_gefuellte_zu_kurze_oder_leerzeichenlose_nachricht_bleibt_bot_auch_ohne_pflicht, tests/Feature/AnfrageWebsiteFeldTest.php::test_honeypot_fax_greift_mit_und_ohne_pflicht
AK10 | "Vue-Komponente" | nicht prüfbar: Vue-Komponente, Nachweis per Kompilieren und Rendern im NACHWEIS
AK11 | "Standard-View reicht die Config durch" | tests/Feature/AnfrageWebsiteFeldTest.php::test_show_view_uebergibt_die_config_an_die_komponente
AK12 | "Dokumentiert" | tests/Feature/AnfrageWebsiteFeldTest.php::test_readme_dokumentiert_beide_schluessel_props_spalte_und_hinweise
Randfall 1 | "Website mit nur Leerzeichen" | tests/Feature/AnfrageWebsiteFeldTest.php::test_website_nur_aus_leerzeichen_gilt_als_leer
Randfall 2 | "nachricht_pflicht=false und Nachricht nur Leerzeichen" | tests/Feature/AnfrageWebsiteFeldTest.php::test_nachricht_nur_aus_leerzeichen_gilt_je_nach_einstellung_als_leer
Randfall 3 | "feld_website=true ohne ausgeführte Migration" | tests/Feature/AnfrageWebsiteFeldTest.php::test_feld_website_an_ohne_migration_wirft_datenbankfehler_beim_speichern
Randfall 4 | "Bestehende Datensätze" | tests/Feature/AnfrageWebsiteFeldTest.php::test_bestehende_datensaetze_behalten_website_null_ohne_nachbefuellen
Randfall 5 | "Mail-Betreff, Absender, Reply-To und Zeilen unverändert" | tests/Feature/AnfrageWebsiteFeldTest.php::test_mail_zeigt_website_direkt_nach_telefon_und_vor_mitteilung_in_html_und_text
AK13 | "Gespeicherte Nachricht ist getrimmt" | tests/Feature/AnfrageNachrichtRandTest.php::test_gespeicherte_nachricht_ist_getrimmt_in_beiden_einstellungen, tests/Feature/AnfrageNachrichtRandTest.php::test_readme_nennt_das_trimmen_der_nachricht
AK14 | "Nachricht keine Zeichenkette" | tests/Feature/AnfrageNachrichtRandTest.php::test_nachricht_als_liste_oder_nicht_zeichenkette_leitet_zur_bot_seite
