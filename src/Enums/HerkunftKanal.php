<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Enums;

enum HerkunftKanal: string
{
    case GoogleAds = 'google_ads';
    case OrganischSonstige = 'organisch/sonstige';
    case Direkt = 'direkt';
}
