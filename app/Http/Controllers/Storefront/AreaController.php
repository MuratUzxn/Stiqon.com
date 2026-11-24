<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * The two areas of the shop. For now each shows what it will contain; the
 * product galleries replace this with the catalogue.
 */
class AreaController extends Controller
{
    public const AREAS = [
        'sticker' => [
            'name' => 'Sticker',
            'lead' => 'Rund, quadratisch, rechteckig, oval oder mit Konturschnitt – auf weißem Vinyl, transparenter und holografischer Folie oder Kraftpapier.',
        ],
        'flyer' => [
            'name' => 'Flyer',
            'lead' => 'Von DIN A6 bis DIN A4, DIN lang und quadratisch – als einzelnes Blatt oder im Stapel gezeigt.',
        ],
    ];

    public function show(string $area): View
    {
        abort_unless(isset(self::AREAS[$area]), 404);

        return view('storefront.area', ['slug' => $area, 'area' => self::AREAS[$area]]);
    }
}
