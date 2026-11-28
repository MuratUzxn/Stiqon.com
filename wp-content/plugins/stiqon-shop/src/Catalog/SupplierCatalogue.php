<?php

namespace Stiqon\Shop\Catalog;

use InvalidArgumentException;
use RuntimeException;
use WC_Product_Simple;
use WP_Term;

/**
 * Public product families from print suppliers. Family keys are local
 * identifiers and stay stable when a display name changes.
 */
final class SupplierCatalogue
{
    public const PROVIDERS = [
        'print-com' => [
            'name' => 'Print.com',
            'website' => 'https://www.print.com/de/',
            'integration' => 'https://developer.print.com/reference/introduction',
            'families' => [
                'sticker' => 'Aufkleber und Etiketten',
                'flyer' => 'Flyer und Falzflyer',
                'visitenkarten' => 'Visitenkarten',
                'karten' => 'Gruß- und Postkarten',
                'buecher' => 'Bücher, Magazine und Broschüren',
                'werbetechnik' => 'Banner, Displays und Schilder',
                'buero' => 'Bürobedarf',
                'verpackung' => 'Verpackungen und Tüten',
                'textilien' => 'Bekleidung und Textilien',
                'werbeartikel' => 'Werbeartikel',
                'gastronomie' => 'Gastronomiebedarf',
                'dekoration' => 'Dekoration und Einrichtung',
                'events' => 'Eventbedarf',
            ],
        ],
        'helloprint-connect' => [
            'name' => 'HelloPrint Connect',
            'website' => 'https://connect.helloprint.de/landing',
            'integration' => 'https://developers.helloprint.com/reference/quotes',
            'families' => [
                'sticker' => 'Aufkleber',
                'flyer' => 'Flyer',
                'visitenkarten' => 'Visitenkarten',
                'broschueren' => 'Broschüren',
                'buero' => 'Bürobedarf und Geschäftsausstattung',
                'werbetechnik' => 'Banner und Außenschilder',
                'verpackung' => 'Verpackungen',
                'werbeartikel' => 'Werbeartikel und Trinkgefäße',
                'textilien' => 'Bekleidung',
            ],
        ],
        'onlineprinters' => [
            'name' => 'Onlineprinters',
            'website' => 'https://www.onlineprinters.de/c/lp/reseller',
            'integration' => 'https://www.onlineprinters.de/c/lp/op-connect',
            'families' => [
                'sticker' => 'Aufkleber und Etiketten',
                'flyer' => 'Flyer und Falzflyer',
                'visitenkarten' => 'Visitenkarten',
                'buecher' => 'Broschüren und Bücher',
                'kalender' => 'Kalender',
                'buero' => 'Karten und Geschäftsausstattung',
                'plakate' => 'Plakate und Poster',
                'werbetechnik' => 'Banner, Fahnen, Roll-ups und Schilder',
                'werbeartikel' => 'Werbeartikel',
                'textilien' => 'Bekleidung und Textilien',
                'verpackung' => 'Verpackungen',
            ],
        ],
        'saxoprint' => [
            'name' => 'SAXOPRINT',
            'website' => 'https://www.saxoprint.de/',
            'integration' => 'https://www.saxoprint.de/schnittstelle',
            'families' => [
                'sticker' => 'Aufkleber',
                'flyer' => 'Flyer und Falzflyer',
                'visitenkarten' => 'Visitenkarten',
                'broschueren' => 'Broschüren und Kataloge',
                'buero' => 'Karten und Geschäftsausstattung',
                'kalender' => 'Kalender',
                'plakate' => 'Plakate und Poster',
                'werbetechnik' => 'Banner, Roll-ups, Fahnen und Schilder',
                'verpackung' => 'Verpackungen',
            ],
        ],
        'flyeralarm-pro' => [
            'name' => 'FLYERALARM PRO',
            'website' => 'https://flyeralarm.pro/',
            'integration' => 'https://resellerapi.flyeralarm.com/docs/index.html',
            'families' => [
                'sticker' => 'Aufkleber und Etiketten',
                'flyer' => 'Flyer',
                'visitenkarten' => 'Visitenkarten',
                'broschueren' => 'Broschüren und Magazine',
                'plakate' => 'Plakate',
                'buero' => 'Karten und Geschäftsausstattung',
                'werbetechnik' => 'Banner und Schilder',
                'werbeartikel' => 'Werbeartikel und Trinkgefäße',
                'verpackung' => 'Verpackungen',
                'textilien' => 'Bekleidung und Textilien',
            ],
        ],
        'probo' => [
            'name' => 'Probo',
            'website' => 'https://www.probo.de/',
            'integration' => 'https://apidocs.proboprints.com/',
            'families' => [
                'sticker' => 'Aufkleber, Aufkleberbögen und Rollenetiketten',
                'schilder' => 'Schilder und Platten',
                'grossformat' => 'Großformatdruck',
                'banner' => 'Banner und Fahnen',
                'displays' => 'Roll-ups und Eventdisplays',
                'dekoration' => 'Fotodruck, Wanddekoration und Tapeten',
                'flyer' => 'Flyer',
                'visitenkarten' => 'Visitenkarten',
                'broschueren' => 'Broschüren',
                'karten' => 'Postkarten',
                'plakate' => 'Plakate',
                'textiltransfers' => 'Textiltransfers',
            ],
        ],
        'print24' => [
            'name' => 'print24',
            'website' => 'https://print24.com/de',
            'integration' => 'https://upi.unitedprint.com/upimgt/downloads/USS_UP_interface_documentation_DE.pdf',
            'families' => [
                'sticker' => 'Aufkleber und Etiketten',
                'flyer' => 'Flyer und Falzflyer',
                'visitenkarten' => 'Visitenkarten',
                'buecher' => 'Broschüren, Bücher und Kataloge',
                'kalender' => 'Kalender',
                'karten' => 'Karten',
                'plakate' => 'Plakate und Poster',
                'werbetechnik' => 'Banner, Fahnen und Roll-ups',
                'textilien' => 'Textilien',
                'verpackung' => 'Verpackungen',
                'fotoprodukte' => 'Fotoprodukte',
                'werbeartikel' => 'Werbeartikel',
            ],
        ],
        'maxilia' => [
            'name' => 'Maxilia',
            'website' => 'https://www.maxilia.de/reseller/',
            'families' => [
                'buero' => 'Schreibgeräte und Büroartikel',
                'taschen' => 'Taschen und Tüten',
                'trinkgefaesse' => 'Tassen, Flaschen und Trinkgefäße',
                'lebensmittel' => 'Lebensmittel und Süßigkeiten',
                'giveaways' => 'Giveaways',
                'technik' => 'Technik-Werbeartikel',
                'textilien' => 'Textilien und Handtücher',
                'outdoor' => 'Outdoor-Werbeartikel',
            ],
        ],
        'printful' => [
            'name' => 'Printful',
            'website' => 'https://www.printful.com/de',
            'integration' => 'https://developers.printful.com/docs/',
            'families' => [
                'bekleidung' => 'T-Shirts, Hoodies und Kinderbekleidung',
                'kopfbedeckungen' => 'Caps und Mützen',
                'taschen' => 'Taschen',
                'handyhuellen' => 'Handyhüllen',
                'wandkunst' => 'Poster, Leinwände und Fahnen',
                'wohnartikel' => 'Kissen, Decken und Wohnartikel',
                'trinkgefaesse' => 'Tassen, Flaschen und Becher',
                'karten' => 'Karten und Papeterie',
                'sticker' => 'Aufkleber',
            ],
        ],
        'shirtigo' => [
            'name' => 'Shirtigo',
            'website' => 'https://cockpit.shirtigo.com/de/catalog',
            'integration' => 'https://cockpit.shirtigo.com/de/integrationen',
            'families' => [
                'shirts' => 'T-Shirts',
                'hoodies' => 'Hoodies',
                'kinderbekleidung' => 'Kinderbekleidung',
                'taschen' => 'Stofftaschen',
                'tassen' => 'Tassen',
                'holzprodukte' => 'Holzprodukte',
                'fussmatten' => 'Fußmatten',
            ],
        ],
        'gelato' => [
            'name' => 'Gelato',
            'website' => 'https://www.gelato.com/de',
            'integration' => 'https://www.gelato.com/de/integrationen',
            'families' => [
                'wandkunst' => 'Poster, Leinwände und Wandbilder',
                'kalender' => 'Kalender',
                'karten' => 'Karten und Postkarten',
                'fotobuecher' => 'Fotobücher',
                'textilien' => 'T-Shirts, Hoodies und Kinderbekleidung',
                'taschen' => 'Tragetaschen',
                'tassen' => 'Tassen',
                'handyhuellen' => 'Handyhüllen',
                'schreibwaren' => 'Schreibwaren',
            ],
        ],
    ];

    public const PROVIDER_META = '_stiqon_supplier';
    public const FAMILY_META = '_stiqon_supplier_family';

    /** @return array{created: int, existing: int} */
    public static function createDrafts(string $provider): array
    {
        $catalogue = self::PROVIDERS[$provider] ?? throw new InvalidArgumentException('Unbekannter Druckanbieter.');

        if (! class_exists(WC_Product_Simple::class)) {
            throw new RuntimeException('WooCommerce ist nicht aktiv.');
        }

        global $wpdb;
        // A database lock covers both the lookup and save, even across PHP workers.
        $lock = 'stiqon:'.sha1(DB_NAME.':'.$wpdb->posts.':'.$provider);
        if ((string) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 0)', $lock)) !== '1') {
            throw new RuntimeException('Der Import läuft bereits oder konnte nicht gestartet werden.');
        }

        try {
            $products = get_posts([
                'post_type' => 'product',
                'post_status' => array_values(get_post_stati()),
                'numberposts' => -1,
                'meta_key' => self::PROVIDER_META,
                'meta_value' => $provider,
            ]);
            $existingFamilies = [];
            foreach ($products as $product) {
                $key = get_post_meta($product->ID, self::FAMILY_META, true);
                if (is_string($key)) {
                    $existingFamilies[$key] = true;
                }
            }

            $result = ['created' => 0, 'existing' => 0];
            foreach ($catalogue['families'] as $family => $title) {
                $key = $provider.':'.$family;
                if (isset($existingFamilies[$key])) {
                    $result['existing']++;
                    continue;
                }

                $product = new WC_Product_Simple();
                $product->set_name($title.' ('.$catalogue['name'].')');
                $product->set_status('draft');
                $product->set_catalog_visibility('hidden');
                if (in_array($family, Spec::TYPES, true)) {
                    $category = get_term_by('slug', $family, 'product_cat');
                    if ($category instanceof WP_Term) {
                        $product->set_category_ids([(int) $category->term_id]);
                    }
                }
                $product->add_meta_data(self::PROVIDER_META, $provider, true);
                $product->add_meta_data(self::FAMILY_META, $key, true);

                if (! $product->save()) {
                    throw new RuntimeException('Ein Produktentwurf konnte nicht gespeichert werden.');
                }
                $result['created']++;
            }

            return $result;
        } finally {
            $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock));
        }
    }
}
