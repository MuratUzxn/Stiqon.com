<?php
/**
 * Every area of the shop at a glance: one tile per area, with the picture Stiqon
 * supplied (assets/brand/sortiment) or, where there is none, the shop's drawing
 * of a typical product. Each tile leads to its area. Below, an enquiry by e-mail
 * for whatever the assortment does not hold. No print supplier is named – they
 * stay behind the scenes (Produkte › Druckanbieter in the administration).
 */

defined('ABSPATH') || exit;

$areas = [];

foreach (stiqon_area_definitions() as $slug => $area) {
    $photo = $area['photo'] ?? null;

    // A picture that is not there (yet) is replaced by the drawing.
    if ($photo !== null && ! is_file(get_theme_file_path("assets/brand/sortiment/{$photo['file']}.webp"))) {
        $photo = null;
    }

    $category = get_term_by('slug', $slug, 'product_cat');

    $areas[] = [
        'name' => $area['label'],
        'url' => stiqon_page_url($slug),
        'photo' => $photo,
        'drawing' => $area['drawing'],
        'products' => $category instanceof WP_Term ? (int) $category->count : 0,
    ];
}

$email = (string) (get_option('woocommerce_email_from_address') ?: get_option('admin_email'));
$enquiry = $email !== '' && is_email($email) ? 'mailto:'.antispambot($email).'?subject='.rawurlencode('Anfrage über stiqon.com') : null;
?>
<?php // Links to "#werbemittel" (Sortiment ansehen) land on the heading. ?>
<section class="wrap pb-16 lg:pb-28" aria-labelledby="werbemittel">
    <?php echo stiqon_section_heading([
        'id' => 'werbemittel',
        'title' => 'Unser Sortiment',
        'lead' => 'Alles mit Ihrem Motiv in der Vorschau – und direkt hier im Shop bestellt.',
    ]); ?>
    <ul class="mt-10 grid grid-cols-2 gap-x-3 gap-y-6 sm:grid-cols-3 sm:gap-x-4 lg:mt-14 lg:grid-cols-4">
        <?php foreach ($areas as $area) : ?>
            <li class="group relative">
                <div class="overflow-hidden rounded-card bg-mist">
                    <?php if ($area['photo'] !== null) : ?>
                        <img
                            src="<?php echo esc_url(get_theme_file_uri("assets/brand/sortiment/{$area['photo']['file']}.webp")); ?>"
                            width="400" height="320" loading="lazy" decoding="async"
                            alt="<?php echo esc_attr($area['photo']['alt']); ?>"
                            class="aspect-[5/4] w-full object-cover transition-transform duration-300 ease-soft group-hover:scale-[1.03]"
                        >
                    <?php else : ?>
                        <?php // Decorative: the area's name below is the tile's text. ?>
                        <div class="flex aspect-[5/4] items-center justify-center transition-transform duration-300 ease-soft group-hover:scale-[1.03]" aria-hidden="true">
                            <?php echo stiqon_mockup($area['drawing']['type'], $area['drawing']['spec'], 'w-[52%]'); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <p class="mt-3 flex flex-wrap items-baseline justify-between gap-x-2 px-1">
                    <?php // The name is the link, stretched over the whole tile. ?>
                    <a href="<?php echo esc_url($area['url']); ?>" class="text-heading rounded-sm after:absolute after:inset-0"><?php echo esc_html($area['name']); ?></a>
                    <span class="text-label text-muted"><?php echo esc_html(match ($area['products']) {
                        0 => 'Demnächst',
                        1 => '1 Produkt',
                        default => $area['products'].' Produkte',
                    }); ?></span>
                </p>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if ($enquiry !== null) : ?>
        <div class="mt-10 flex flex-col items-center gap-4 text-center lg:mt-14">
            <p class="text-muted">Etwas anderes im Sinn? Schreiben Sie uns, was Sie brauchen – Sie bekommen ein Angebot.</p>
            <?php echo stiqon_button('Anfrage per E-Mail', ['variant' => 'secondary', 'href' => $enquiry]); ?>
        </div>
    <?php endif; ?>
</section>
