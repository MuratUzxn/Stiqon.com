<?php

namespace Stiqon\Shop\Tests\Admin;

use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Admin\ProductPreview;

/**
 * What the tab "Vorschau" of a product stores from what its fields send.
 */
final class ProductPreviewTest extends TestCase
{
    public function test_automatic_stores_nothing(): void
    {
        $this->assertSame([], ProductPreview::chosen(['type' => '', 'shape' => '', 'format' => '', 'sticker_motif' => '', 'flyer_motif' => ''], 'Runde Sticker', ['Sticker']));
        $this->assertSame([], ProductPreview::chosen([], 'Runde Sticker'));
    }

    public function test_a_shape_is_kept_for_a_product_the_names_make_a_sticker(): void
    {
        $this->assertSame(['shape' => 'square'], ProductPreview::chosen(['type' => '', 'shape' => 'square'], 'Runde Sticker'));
        $this->assertSame(['shape' => 'oval', 'motif' => 'sonne'], ProductPreview::chosen(['shape' => 'oval', 'sticker_motif' => 'sonne'], 'Etiketten', ['Sticker']));
    }

    public function test_only_the_fields_of_the_products_kind_count(): void
    {
        // The form sends the fields of both kinds; the hidden ones may hold anything.
        $sent = ['type' => 'flyer', 'shape' => 'square', 'format' => 'a5', 'sticker_motif' => 'sonne', 'flyer_motif' => 'fest'];

        $this->assertSame(['type' => 'flyer', 'format' => 'a5', 'motif' => 'fest'], ProductPreview::chosen($sent, 'Runde Sticker', ['Sticker']));
        $this->assertSame(['type' => 'sticker', 'shape' => 'square', 'motif' => 'sonne'], ProductPreview::chosen(['type' => 'sticker'] + $sent, 'Flyer DIN A5'));
    }

    public function test_values_the_drawings_do_not_know_are_dropped(): void
    {
        $this->assertSame([], ProductPreview::chosen(['type' => 'tapete', 'shape' => 'hexagon', 'sticker_motif' => '../../wp-config'], 'Runde Sticker'));
        $this->assertSame(['type' => 'flyer'], ProductPreview::chosen(['type' => 'flyer', 'format' => 'a3', 'flyer_motif' => 'siegel'], 'Plakat'));
    }

    public function test_without_a_kind_nothing_about_the_drawing_is_kept(): void
    {
        // Neither the fields nor the names say what this is: a shape alone would mean nothing.
        $this->assertSame([], ProductPreview::chosen(['shape' => 'square', 'format' => 'a5'], 'Tasse', ['Geschenke']));
    }
}
