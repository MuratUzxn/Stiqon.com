<?php
/**
 * Opens every page: document head, skip link and the header with the two
 * registers of the site – "Design" and "Produkte", as mobbin.com has "Apps" and
 * "Sites" – the search, the motif button, the cart and the menu for small
 * screens. The areas and the references are in the menu and in the footer; an
 * area page links the other one itself.
 */

defined('ABSPATH') || exit;

$registers = stiqon_registers();
$areas = stiqon_navigation();
$cart = stiqon_cart_link();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <?php // Controls that only work with the page's script are not offered without it. ?>
    <?php // What stands in for them is shown only then (data-without-script, otherwise hidden by an inline style). ?>
    <noscript><style>[data-needs-script] { display: none !important; } [data-without-script] { display: revert !important; }</style></noscript>
</head>
<body <?php body_class('flex min-h-screen flex-col'); ?>>
<?php wp_body_open(); ?>
<a
    href="#inhalt"
    class="text-action sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-full focus:bg-ink focus:px-5 focus:py-3 focus:text-paper"
>Zum Inhalt springen</a>

<header class="relative z-40 bg-paper">
    <div class="wrap flex h-18 items-center gap-3 lg:h-23 lg:gap-6">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="shrink-0 rounded-sm" aria-label="Stiqon – zur Startseite">
            <?php echo stiqon_logo(['class' => 'h-8 w-auto']); ?>
        </a>

        <?php // As on the model: plain words, the current register in ink, the other in grey – no underline, no pill. ?>
        <?php // Below 768 px they do not fit beside search, cart and menu with room to tap; there they open the menu. ?>
        <nav aria-label="Hauptnavigation" class="hidden items-center gap-1 md:flex lg:ml-2">
            <?php foreach ($registers as $register) : ?>
                <a
                    href="<?php echo esc_url($register['url']); ?>"<?php echo $register['current'] !== null ? ' aria-current="'.esc_attr($register['current']).'"' : ''; ?>
                    class="<?php echo esc_attr(stiqon_classes(['text-action inline-flex h-11 items-center rounded-full px-2.5 text-[1.0625rem] transition-colors duration-200 ease-soft', 'text-ink' => $register['current'] !== null, 'text-muted hover:text-ink' => $register['current'] === null])); ?>"
                ><?php echo esc_html($register['label']); ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="ml-auto flex items-center gap-2">
            <?php // Search: a link to the results page. The page's script opens the search over the page instead. ?>
            <a
                href="<?php echo esc_url(stiqon_search_url()); ?>" data-search-open
                class="group/search text-action inline-flex h-11 shrink-0 items-center gap-2 rounded-full transition-colors duration-200 ease-soft max-lg:size-11 max-lg:justify-center max-lg:hover:bg-ink/8 lg:w-48 lg:bg-mist lg:px-4 lg:hover:bg-mist-deep"
            >
                <?php echo stiqon_icon('search'); ?>
                <span class="max-lg:sr-only lg:font-medium lg:text-muted lg:group-hover/search:text-ink">Suche</span>
                <kbd class="ml-auto rounded-md bg-paper px-1.5 py-0.5 font-sans text-xs font-semibold text-muted max-lg:hidden pointer-coarse:hidden" aria-hidden="true" data-search-shortcut data-needs-script>Strg K</kbd>
            </a>
            <?php echo stiqon_motif_button('Eigenes Motiv', ['variant' => 'secondary', 'class' => 'max-sm:hidden']); ?>

            <?php if (($account = stiqon_account_url()) !== null) : ?>
                <a href="<?php echo esc_url($account); ?>" aria-label="Kundenkonto" class="inline-flex size-11 shrink-0 items-center justify-center rounded-full transition-colors duration-200 ease-soft hover:bg-ink/8 max-md:hidden">
                    <?php echo stiqon_icon('user'); ?>
                </a>
            <?php endif; ?>

            <?php // Announced on the pages of the shop only, a visitor's cart would lead to the announcement. ?>
            <?php if ($cart !== null && ! stiqon_shop_is_concealed()) : ?>
                <a
                    href="<?php echo esc_url($cart['url']); ?>" aria-label="<?php echo esc_attr($cart['label']); ?>" data-cart-count="<?php echo esc_attr((string) $cart['count']); ?>"
                    data-cart-hash="<?php echo esc_attr($cart['hash']); ?>" data-cart-source="<?php echo esc_url($cart['source']); ?>"
                    class="relative inline-flex size-11 shrink-0 items-center justify-center rounded-full transition-colors duration-200 ease-soft hover:bg-ink/8"
                >
                    <?php echo stiqon_icon('bag'); ?>
                    <?php // Always there, so the page's script can keep it in step where the cart changes without a new page. ?>
                    <span class="absolute top-1 right-0 inline-flex h-4.5 min-w-4.5 items-center justify-center rounded-full bg-ink px-1 text-[0.6875rem] leading-none font-semibold text-paper [&[hidden]]:hidden" aria-hidden="true"<?php echo $cart['count'] > 0 ? '' : ' hidden'; ?>><?php echo esc_html((string) $cart['count']); ?></span>
                </a>
            <?php endif; ?>

            <?php // The menu for small screens is a plain disclosure: it opens without any script. ?>
            <details class="group/menu md:hidden">
                <summary class="inline-flex size-11 cursor-pointer list-none items-center justify-center rounded-full text-ink transition-colors hover:bg-ink/8 [&::-webkit-details-marker]:hidden">
                    <span class="sr-only">Menü</span>
                    <?php echo stiqon_icon('menu', 'group-open/menu:hidden'); ?>
                    <?php echo stiqon_icon('x', 'hidden group-open/menu:block'); ?>
                </summary>
                <div class="absolute inset-x-0 top-full rounded-b-card bg-paper pb-2 shadow-[0_24px_32px_-16px_rgb(0_0_0/0.18)]">
                    <nav aria-label="Hauptnavigation" class="wrap flex flex-col gap-1 pt-1 pb-4">
                        <?php // The registers first, large, as in the header; then what lies within the products, and beside them. ?>
                        <div class="mb-2 flex flex-col">
                            <?php foreach ($registers as $register) : ?>
                                <a
                                    href="<?php echo esc_url($register['url']); ?>"<?php echo $register['current'] !== null ? ' aria-current="'.esc_attr($register['current']).'"' : ''; ?>
                                    class="<?php echo esc_attr(stiqon_classes(['text-heading flex h-13 items-center rounded-control px-4 text-[1.625rem] hover:bg-mist', 'text-ink' => $register['current'] !== null, 'text-muted' => $register['current'] === null])); ?>"
                                ><?php echo esc_html($register['label']); ?></a>
                            <?php endforeach; ?>
                        </div>
                        <?php foreach ($areas as $area) : ?>
                            <a
                                href="<?php echo esc_url($area['url']); ?>"<?php echo $area['current'] ? ' aria-current="page"' : ''; ?>
                                class="<?php echo esc_attr(stiqon_classes(['text-action flex h-12 items-center rounded-control px-4 text-lg', 'bg-mist' => $area['current'], 'hover:bg-mist' => ! $area['current']])); ?>"
                            ><?php echo esc_html($area['label']); ?></a>
                        <?php endforeach; ?>
                        <?php if ($account !== null) : ?>
                            <a href="<?php echo esc_url($account); ?>" class="text-action flex h-12 items-center rounded-control px-4 text-lg hover:bg-mist">Kundenkonto</a>
                        <?php endif; ?>
                        <?php echo stiqon_motif_button('Eigenes Motiv ausprobieren', ['variant' => 'secondary', 'class' => 'mt-3 w-full sm:hidden']); ?>
                    </nav>
                </div>
            </details>
        </div>
    </div>
</header>

<main id="inhalt" class="flex-1">
