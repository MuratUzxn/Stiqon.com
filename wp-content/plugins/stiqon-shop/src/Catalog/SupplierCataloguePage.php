<?php

namespace Stiqon\Shop\Catalog;

use Exception;
use WC_Product_Simple;

final class SupplierCataloguePage
{
    private const PAGE = 'stiqon-supplier-catalogue';
    private const ACTION = 'stiqon_supplier_catalogue_import';
    private const CAPABILITY = 'edit_products';

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'addMenu']);
        add_action('admin_post_'.self::ACTION, [self::class, 'import']);
    }

    public static function addMenu(): void
    {
        if (class_exists(WC_Product_Simple::class)) {
            add_submenu_page(
                'edit.php?post_type=product',
                'Druckanbieter',
                'Druckanbieter',
                self::CAPABILITY,
                self::PAGE,
                [self::class, 'render'],
            );
        }
    }

    public static function render(): void
    {
        if (! current_user_can(self::CAPABILITY)) {
            wp_die('Sie dürfen auf diesen Bereich nicht zugreifen.', '', ['response' => 403]);
        }

        $created = self::countQueryParameter('created');
        $existing = self::countQueryParameter('existing');
        ?>
        <div class="wrap">
            <h1>Druckanbieter</h1>
            <p>Öffentlich gelistete Produktfamilien. Der Import legt pro Produktfamilie einen ausgeblendeten WooCommerce-Entwurf ohne Preis oder Varianten an.</p>
            <?php if ($created !== null && $existing !== null) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html(sprintf('Entwürfe angelegt: %d. Bereits vorhanden: %d.', $created, $existing)); ?></p>
                </div>
            <?php endif; ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th scope="col">Anbieter</th>
                        <th scope="col">Produktfamilien</th>
                        <th scope="col">Import</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (SupplierCatalogue::PROVIDERS as $key => $provider) : ?>
                        <tr>
                            <th scope="row">
                                <a href="<?php echo esc_url($provider['website']); ?>"><?php echo esc_html($provider['name']); ?></a>
                                <?php if (isset($provider['integration'])) : ?>
                                    <br><a href="<?php echo esc_url($provider['integration']); ?>">Schnittstelle</a>
                                <?php endif; ?>
                            </th>
                            <td><?php echo esc_html(implode(' · ', $provider['families'])); ?></td>
                            <td>
                                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                                    <input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION); ?>">
                                    <input type="hidden" name="provider" value="<?php echo esc_attr($key); ?>">
                                    <?php wp_nonce_field(self::ACTION.'_'.$key, '_wpnonce', false); ?>
                                    <button type="submit" class="button" aria-label="<?php echo esc_attr('Entwürfe für '.$provider['name'].' anlegen'); ?>">Entwürfe anlegen</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function import(): void
    {
        if (! current_user_can(self::CAPABILITY)) {
            wp_die('Sie dürfen keine Produkte anlegen.', '', ['response' => 403]);
        }

        $provider = $_POST['provider'] ?? '';
        if (! is_string($provider) || ! isset(SupplierCatalogue::PROVIDERS[$provider])) {
            wp_die('Unbekannter Druckanbieter.', '', ['response' => 400]);
        }

        check_admin_referer(self::ACTION.'_'.$provider);

        try {
            $result = SupplierCatalogue::createDrafts($provider);
        } catch (Exception $exception) {
            if (function_exists('wc_get_logger')) {
                wc_get_logger()->error($exception->getMessage(), ['source' => 'stiqon-supplier-catalogue']);
            }
            wp_die('Der Import konnte nicht abgeschlossen werden. Starten Sie ihn erneut, um fehlende Entwürfe anzulegen.', '', ['response' => 500, 'back_link' => true]);
        }

        $url = add_query_arg(['post_type' => 'product', 'page' => self::PAGE] + $result, admin_url('edit.php'));
        wp_safe_redirect($url, 303);
        exit;
    }

    private static function countQueryParameter(string $key): ?int
    {
        $value = $_GET[$key] ?? null;

        return is_string($value) && ctype_digit($value) ? (int) $value : null;
    }
}
