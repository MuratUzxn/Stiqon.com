<?php

namespace Stiqon\Shop\Security;

use WP_Error;

/**
 * A failed login says the same whether or not the address or name belongs to
 * an account, so nobody can find out by trying who shops here. WordPress and
 * WooCommerce answer "Unbekannte E-Mail-Adresse" for one, and name the address
 * for the other. (Registration and "Passwort vergessen" still tell: that is how
 * WooCommerce works – see the README.)
 */
final class LoginMessages
{
    private const TELLING = ['invalid_username', 'invalid_email', 'incorrect_password'];

    public static function register(): void
    {
        add_filter('authenticate', [self::class, 'neutral'], 100);
    }

    public static function neutral(mixed $user): mixed
    {
        if (! $user instanceof WP_Error || array_intersect($user->get_error_codes(), self::TELLING) === []) {
            return $user;
        }

        return new WP_Error('stiqon_login_failed', sprintf(
            'Die Anmeldung hat nicht geklappt: Benutzername oder E-Mail-Adresse und Passwort passen nicht zusammen. <a href="%s">Passwort vergessen?</a>',
            esc_url(wp_lostpassword_url()),
        ));
    }
}
