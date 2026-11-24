<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Internal sample sheet ("Musterbogen"): colours, type, logo versions and
 * mockups on one page, for reviewing the look. Not part of the public shop.
 */
class SampleSheetController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(app()->environment('local', 'testing'), 404);

        return view('storefront.internal.sample-sheet');
    }
}
