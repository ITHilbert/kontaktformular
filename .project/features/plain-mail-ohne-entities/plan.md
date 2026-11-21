# Plan: plain-mail-ohne-entities

Marketing: entfällt (kein Seitendesign)
Review: entfällt (nur Plain-Mail-View, Klartext ohne HTML-Kontext, HTML-Teil unverändert per AK3 geprüft; keine Route, keine Migration)

## T1 [junior] Plain-View unescaped (AK1–AK3)
— Tests: tests/Feature/PlainMailEntitiesTest.php
Bestehende Tests: tests/Feature/AnfrageHerkunftTest.php tests/Feature/AnfrageBccTest.php tests/Feature/AnfrageWebsiteFeldTest.php

Stand: Spec geschrieben, Test-Writer als Nächstes.
