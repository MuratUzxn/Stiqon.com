<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Renders admin avatars as an inline SVG with initials.
 *
 * Filament's default provider loads avatars from ui-avatars.com; the shop must
 * not send any request (and no admin name) to third parties.
 */
class LocalInitialsAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        $initials = str(Filament::getNameForDefaultAvatar($record))
            ->trim()
            ->explode(' ')
            ->map(function (string $segment): string {
                $letters = preg_replace('/^[^\p{L}\p{N}]+/u', '', $segment) ?? '';

                return mb_strtoupper(mb_substr($letters, 0, 1));
            })
            ->filter()
            ->take(2)
            ->join('');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" fill="#0a0a0a"/>'
            .'<text x="32" y="33" fill="#ffffff" font-family="system-ui, sans-serif" font-size="24" '
            .'font-weight="600" text-anchor="middle" dominant-baseline="central">'
            .htmlspecialchars($initials !== '' ? $initials : '?', ENT_XML1 | ENT_QUOTES, 'UTF-8')
            .'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
