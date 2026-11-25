<?php
/**
 * The print suppliers' product families on the home page, grouped into the
 * categories of SupplierCategories. Each category opens like the questions
 * further down; without the plugin the section is left out.
 */

use Stiqon\Shop\Catalog\SupplierCategories;

defined('ABSPATH') || exit;

if (! class_exists(SupplierCategories::class)) {
    return;
}
?>
<section class="wrap pb-16 lg:pb-28" aria-labelledby="drucksortiment">
    <?php echo stiqon_section_heading([
        'id' => 'drucksortiment',
        'title' => 'Sortimente der Druckanbieter',
        'lead' => 'Öffentlich gelistete Produktfamilien im Überblick. Produktdetails und Verfügbarkeit findest du beim jeweiligen Anbieter.',
    ]); ?>
    <div class="mt-10 grid items-start gap-3 sm:grid-cols-2 sm:gap-4 lg:mt-14 lg:grid-cols-3 xl:grid-cols-4">
        <?php foreach (SupplierCategories::grouped() as $slug => $category) : ?>
            <details class="group rounded-card bg-mist">
                <summary class="flex cursor-pointer list-none flex-col gap-4 rounded-card p-3 pb-5 [&::-webkit-details-marker]:hidden">
                    <?php // A drawing of the category: a product view where the shop has one, otherwise a simple shape. ?>
                    <span class="supplier-art supplier-art--<?php echo esc_attr($slug); ?> flex h-28 items-center sm:h-36 justify-center overflow-hidden rounded-control bg-paper" aria-hidden="true">
                        <?php if ($slug === 'aufkleber') : ?>
                            <span class="w-24 sm:w-32"><?php echo stiqon_sticker(['shape' => 'circle', 'motif' => 'sonne']); ?></span>
                        <?php elseif ($slug === 'drucksachen') : ?>
                            <span class="w-20 sm:w-28"><?php echo stiqon_flyer(['format' => 'a5', 'motif' => 'fest', 'stack' => true]); ?></span>
                        <?php else : ?>
                            <span class="supplier-art__shape"></span>
                        <?php endif; ?>
                    </span>
                    <span class="flex items-start justify-between gap-4 px-3">
                        <span>
                            <span class="text-heading block"><?php echo esc_html($category['name']); ?></span>
                            <span class="mt-1 block text-sm text-muted">
                                <span class="whitespace-nowrap"><?php echo esc_html(sprintf('%d %s', count($category['offers']), count($category['offers']) === 1 ? 'Produktfamilie' : 'Produktfamilien')); ?></span> ·
                                <span class="whitespace-nowrap"><?php echo esc_html(sprintf('%d Anbieter', count(array_unique(array_column($category['offers'], 'provider_id'))))); ?></span>
                            </span>
                        </span>
                        <?php echo stiqon_icon('plus', 'mt-0.5 shrink-0 transition-transform duration-200 ease-soft group-open:rotate-45'); ?>
                    </span>
                </summary>
                <?php
                // One entry per supplier: its families in this category, its links once.
                $bySupplier = [];
                foreach ($category['offers'] as $offer) {
                    $bySupplier[$offer['provider_id']] ??= $offer + ['families' => []];
                    $bySupplier[$offer['provider_id']]['families'][] = $offer['family'];
                }
                ?>
                <ul class="px-6 pb-5 text-sm [overflow-wrap:anywhere] [&>li]:border-t [&>li]:border-line [&>li]:py-3">
                    <?php foreach ($bySupplier as $supplier) : ?>
                        <li>
                            <a class="font-semibold underline underline-offset-3" href="<?php echo esc_url($supplier['website']); ?>" rel="noopener"><?php echo esc_html($supplier['provider']); ?></a>
                            <?php if ($supplier['integration'] !== null) : ?>
                                <a class="ml-2 text-muted underline underline-offset-3 hover:text-ink" href="<?php echo esc_url($supplier['integration']); ?>" rel="noopener" aria-label="<?php echo esc_attr('Schnittstelle von '.$supplier['provider']); ?>">Schnittstelle</a>
                            <?php endif; ?>
                            <p class="mt-1 text-muted"><?php echo esc_html(implode(' · ', $supplier['families'])); ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endforeach; ?>
    </div>
</section>
