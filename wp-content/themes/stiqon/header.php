<?php
/**
 * Opens every page: document head, skip link and the header with the two areas,
 * the motif button and the menu for small screens.
 */

defined('ABSPATH') || exit;

$areas = stiqon_areas();
$link = 'text-action inline-flex h-11 items-center rounded-full px-3 underline-offset-[6px] decoration-2 transition-opacity duration-200 hover:opacity-60';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
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

        <nav aria-label="Hauptnavigation" class="hidden items-center md:flex">
            <?php foreach ($areas as $area) : ?>
                <a
                    href="<?php echo esc_url($area['url']); ?>"<?php echo $area['current'] ? ' aria-current="page"' : ''; ?>
                    class="<?php echo esc_attr(stiqon_classes([$link, 'underline' => $area['current']])); ?>"
                ><?php echo esc_html($area['label']); ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="ml-auto flex items-center gap-2">
            <?php echo stiqon_motif_button('Eigenes Motiv', ['variant' => 'secondary', 'class' => 'max-sm:hidden']); ?>

            <?php // The menu for small screens is a plain disclosure: it opens without any script. ?>
            <details class="group/menu md:hidden">
                <summary class="inline-flex size-11 cursor-pointer list-none items-center justify-center rounded-full text-ink transition-colors hover:bg-ink/8 [&::-webkit-details-marker]:hidden">
                    <span class="sr-only">Menü</span>
                    <?php echo stiqon_icon('menu', 'group-open/menu:hidden'); ?>
                    <?php echo stiqon_icon('x', 'hidden group-open/menu:block'); ?>
                </summary>
                <div class="absolute inset-x-0 top-full rounded-b-card bg-paper pb-2 shadow-[0_24px_32px_-16px_rgb(0_0_0/0.18)]">
                    <nav aria-label="Hauptnavigation" class="wrap flex flex-col gap-1 pt-1 pb-4">
                        <?php foreach ($areas as $area) : ?>
                            <a
                                href="<?php echo esc_url($area['url']); ?>"<?php echo $area['current'] ? ' aria-current="page"' : ''; ?>
                                class="<?php echo esc_attr(stiqon_classes(['text-action flex h-12 items-center rounded-control px-4 text-lg', 'bg-mist' => $area['current'], 'hover:bg-mist' => ! $area['current']])); ?>"
                            ><?php echo esc_html($area['label']); ?></a>
                        <?php endforeach; ?>
                        <?php echo stiqon_motif_button('Eigenes Motiv ausprobieren', ['variant' => 'secondary', 'class' => 'mt-3 w-full sm:hidden']); ?>
                    </nav>
                </div>
            </details>
        </div>
    </div>
</header>

<main id="inhalt" class="flex-1">
