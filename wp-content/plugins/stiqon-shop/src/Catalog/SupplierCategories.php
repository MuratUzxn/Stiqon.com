<?php

namespace Stiqon\Shop\Catalog;

final class SupplierCategories
{
    private const GROUPS = [
        'aufkleber' => ['Aufkleber & Etiketten', ['sticker']],
        'drucksachen' => ['Drucksachen & Papeterie', ['flyer', 'visitenkarten', 'broschueren', 'buecher', 'karten', 'kalender', 'buero', 'schreibwaren']],
        'werbetechnik' => ['Werbetechnik & Messe', ['werbetechnik', 'banner', 'grossformat', 'displays', 'events', 'schilder', 'plakate']],
        'verpackungen' => ['Verpackungen & Gastronomie', ['verpackung', 'gastronomie']],
        'textilien' => ['Textilien & Taschen', ['bekleidung', 'textilien', 'textiltransfers', 'hoodies', 'shirts', 'kinderbekleidung', 'kopfbedeckungen', 'taschen']],
        'werbeartikel' => ['Werbeartikel & Zubehör', ['werbeartikel', 'trinkgefaesse', 'tassen', 'giveaways', 'handyhuellen', 'technik', 'lebensmittel', 'outdoor']],
        'wohnen' => ['Foto & Wohnen', ['dekoration', 'fotobuecher', 'fotoprodukte', 'fussmatten', 'holzprodukte', 'wandkunst', 'wohnartikel']],
    ];

    /**
     * Stable category IDs and uniform supplier records for the website and exports.
     * A mixed supplier family stays together under its primary category.
     */
    public static function grouped(): array
    {
        $groups = [];
        $categoryByFamily = [];
        foreach (self::GROUPS as $slug => [$name, $families]) {
            $groups[$slug] = ['name' => $name, 'offers' => []];
            foreach ($families as $family) {
                $categoryByFamily[$family] = $slug;
            }
        }

        foreach (SupplierCatalogue::PROVIDERS as $providerId => $provider) {
            foreach ($provider['families'] as $familyId => $family) {
                $category = $categoryByFamily[$familyId];
                $groups[$category]['offers'][] = [
                    'provider_id' => $providerId,
                    'provider' => $provider['name'],
                    'family_id' => $familyId,
                    'family' => $family,
                    'website' => $provider['website'],
                    'integration' => $provider['integration'] ?? null,
                ];
            }
        }

        return $groups;
    }
}
