<?php

namespace Stiqon\Shop\Tests\Catalog;

use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\SupplierCatalogue;
use Stiqon\Shop\Catalog\SupplierCategories;

/**
 * The home page shows the suppliers' product families by category. Every family
 * has to land in exactly one category: a family without one would break the page.
 */
final class SupplierCategoriesTest extends TestCase
{
    public function test_every_supplier_family_appears_in_exactly_one_category(): void
    {
        $expected = [];
        foreach (SupplierCatalogue::PROVIDERS as $providerId => $provider) {
            foreach (array_keys($provider['families']) as $familyId) {
                $expected[] = $providerId.':'.$familyId;
            }
        }

        $shown = [];
        foreach (SupplierCategories::grouped() as $category) {
            foreach ($category['offers'] as $offer) {
                $shown[] = $offer['provider_id'].':'.$offer['family_id'];
            }
        }

        sort($expected);
        sort($shown);
        $this->assertSame($expected, $shown);
    }

    public function test_every_category_has_a_name_and_something_to_show(): void
    {
        foreach (SupplierCategories::grouped() as $slug => $category) {
            $this->assertMatchesRegularExpression('/^[a-z]+$/', $slug);
            $this->assertNotSame('', $category['name']);
            $this->assertNotEmpty($category['offers'], "Category {$slug} is empty.");
        }
    }

    public function test_offers_carry_the_links_of_their_supplier(): void
    {
        foreach (SupplierCategories::grouped() as $category) {
            foreach ($category['offers'] as $offer) {
                $provider = SupplierCatalogue::PROVIDERS[$offer['provider_id']];
                $this->assertSame($provider['name'], $offer['provider']);
                $this->assertSame($provider['families'][$offer['family_id']], $offer['family']);
                $this->assertStringStartsWith('https://', $offer['website']);
                $this->assertSame($provider['integration'] ?? null, $offer['integration']);
            }
        }
    }
}
