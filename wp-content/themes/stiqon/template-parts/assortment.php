<?php
/**
 * The shop's assortment beyond the drawings: one picture per range, from the
 * pictures Stiqon supplied (assets/brand/sortiment). What the shop sells leads
 * to its area; everything else is supplied on request, by e-mail to the shop.
 * No print supplier is named – they stay behind the scenes (Produkte ›
 * Druckanbieter in the administration).
 */

defined('ABSPATH') || exit;

$ranges = [
    ['slug' => 'aufkleber', 'name' => 'Aufkleber', 'alt' => 'Aufkleber in vier Formen und auf der Rolle, mit Stiqon-Logo', 'area' => 'sticker'],
    ['slug' => 'visitenkarten', 'name' => 'Visitenkarten', 'alt' => 'Zwei Stapel Visitenkarten mit Stiqon-Logo'],
    ['slug' => 'karten', 'name' => 'Karten', 'alt' => 'Klappkarte und Karte mit Stiqon-Logo'],
    ['slug' => 'broschueren', 'name' => 'Broschüren', 'alt' => 'Aufgeschlagene und geschlossene Broschüre mit Stiqon-Logo'],
    ['slug' => 'plakate', 'name' => 'Plakate', 'alt' => 'Plakat mit Stiqon-Logo'],
    ['slug' => 'kalender', 'name' => 'Kalender', 'alt' => 'Tischkalender mit Stiqon-Logo'],
    ['slug' => 'briefpapier', 'name' => 'Briefpapier', 'alt' => 'Briefbogen, Umschlag und Karte mit Stiqon-Logo'],
    ['slug' => 'verpackungen', 'name' => 'Verpackungen', 'alt' => 'Versandkarton und Papiertüte mit Stiqon-Logo'],
    ['slug' => 'textilien', 'name' => 'Textilien', 'alt' => 'Zwei gefaltete T-Shirts mit Stiqon-Logo'],
    ['slug' => 'werbetechnik', 'name' => 'Werbetechnik', 'alt' => 'Roll-up und Kundenstopper mit Stiqon-Logo'],
];

// Only ranges whose picture is there.
$ranges = array_filter($ranges, static fn (array $range): bool => is_file(get_theme_file_path("assets/brand/sortiment/{$range['slug']}.webp")));

if ($ranges === []) {
    return;
}

$email = (string) (get_option('woocommerce_email_from_address') ?: get_option('admin_email'));
$enquiry = $email !== '' && is_email($email) ? 'mailto:'.antispambot($email).'?subject='.rawurlencode('Anfrage über stiqon.com') : null;
?>
<section class="wrap pb-16 lg:pb-28" aria-labelledby="werbemittel">
    <?php echo stiqon_section_heading([
        'id' => 'werbemittel',
        'title' => 'Unser Sortiment',
        'lead' => 'Sticker und Flyer bestellen Sie direkt hier im Shop. Alles andere mit Ihrem Logo liefern wir auf Anfrage.',
    ]); ?>
    <ul class="mt-10 grid grid-cols-2 gap-x-3 gap-y-6 sm:grid-cols-3 sm:gap-x-4 lg:mt-14 lg:grid-cols-5">
        <?php foreach ($ranges as $range) : ?>
            <?php $url = isset($range['area']) ? stiqon_page_url($range['area']) : null; ?>
            <li class="group relative">
                <div class="overflow-hidden rounded-card bg-mist">
                    <img
                        src="<?php echo esc_url(get_theme_file_uri("assets/brand/sortiment/{$range['slug']}.webp")); ?>"
                        width="400" height="320" loading="lazy" decoding="async"
                        alt="<?php echo esc_attr($range['alt']); ?>"
                        class="aspect-[5/4] w-full object-cover<?php echo $url !== null ? ' transition-transform duration-300 ease-soft group-hover:scale-[1.03]' : ''; ?>"
                    >
                </div>
                <p class="mt-3 flex flex-wrap items-baseline justify-between gap-x-2 px-1">
                    <?php if ($url !== null) : ?>
                        <?php // The name is the link, stretched over the whole tile. ?>
                        <a href="<?php echo esc_url($url); ?>" class="text-heading rounded-sm after:absolute after:inset-0"><?php echo esc_html($range['name']); ?></a>
                        <span class="text-label text-muted">Im Shop</span>
                    <?php else : ?>
                        <span class="text-heading"><?php echo esc_html($range['name']); ?></span>
                        <span class="text-label text-muted">Auf Anfrage</span>
                    <?php endif; ?>
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
