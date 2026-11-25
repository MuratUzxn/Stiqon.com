<?php

use Stiqon\Shop\Catalog\SupplierCategories;

defined('ABSPATH') || exit;

if (! class_exists(SupplierCategories::class)) {
    return;
}
?>
<section class="wrap pb-16 lg:pb-28" aria-labelledby="drucksortiment">
    <?php echo stiqon_section_heading(['id' => 'drucksortiment', 'title' => 'Sortimente der Druckanbieter']); ?>
    <p class="supplier-intro">Öffentlich gelistete Produktfamilien im Überblick. Produktdetails und Verfügbarkeit findest du beim jeweiligen Anbieter.</p>
    <div class="supplier-grid">
        <?php foreach (SupplierCategories::grouped() as $slug => $category) : ?>
            <details class="supplier-card">
                <summary>
                    <span class="supplier-art supplier-art--<?php echo esc_attr($slug); ?>" aria-hidden="true">
                        <?php if ($slug === 'aufkleber') : ?>
                            <?php echo stiqon_sticker(['shape' => 'circle', 'motif' => 'sonne']); ?>
                        <?php elseif ($slug === 'drucksachen') : ?>
                            <?php echo stiqon_flyer(['format' => 'a5', 'motif' => 'fest', 'stack' => true]); ?>
                        <?php else : ?>
                            <span></span>
                        <?php endif; ?>
                    </span>
                    <span class="supplier-title"><?php echo esc_html($category['name']); ?></span>
                    <span class="supplier-count"><?php echo esc_html(sprintf('%d Anbieter', count(array_unique(array_column($category['offers'], 'provider_id'))))); ?></span>
                </summary>
                <ul class="supplier-offers">
                    <?php foreach ($category['offers'] as $offer) : ?>
                        <li>
                            <strong><?php echo esc_html($offer['family']); ?></strong>
                            <a href="<?php echo esc_url($offer['website']); ?>"><?php echo esc_html($offer['provider']); ?></a>
                            <?php if ($offer['integration'] !== null) : ?>
                                <a href="<?php echo esc_url($offer['integration']); ?>" aria-label="<?php echo esc_attr('Schnittstelle von '.$offer['provider']); ?>">Schnittstelle</a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endforeach; ?>
    </div>
</section>
