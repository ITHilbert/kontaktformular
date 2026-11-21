# Abdeckung: plain-mail-ohne-entities

Risiko normal.

AK1 | "enthält der Text-Teil die Zeile Site: http://x.test/kontakt?utm_source=a&utm_medium=b und kein &amp;" | tests/Feature/PlainMailEntitiesTest.php::test_text_teil_nennt_site_mit_und_zeichen_ohne_entity
AK2 | "stehen Name und Mitteilung unverändert im Text (ohne &amp;, &lt;, &quot;)" | tests/Feature/PlainMailEntitiesTest.php::test_text_teil_gibt_name_und_mitteilung_unveraendert_aus
AK3 | "steht der Name im HTML-Teil weiterhin escaped (Müller &amp; Söhne) und < der Mitteilung als &lt;" | tests/Feature/PlainMailEntitiesTest.php::test_html_teil_bleibt_escaped
Randfall 1 | "Herkunftszeilen (Referrer mit &) im Text-Teil ebenfalls unescaped" | tests/Feature/PlainMailEntitiesTest.php::test_text_teil_gibt_referrer_mit_und_zeichen_unescaped_aus
