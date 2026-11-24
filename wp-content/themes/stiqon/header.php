<?php
/**
 * Opens every page: document head, skip link, the sticky header with the two
 * areas, the motif button and the menu for small screens.
 */

defined('ABSPATH') || exit;

$areas = stiqon_areas();
$link = 'inline-flex h-10 items-center rounded-full px-4 text-[0.9375rem] font-medium transition-colors duration-200 ease-soft';
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
    class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-full focus:bg-ink focus:px-5 focus:py-3 focus:text-paper"
>Zum Inhalt springen</a>

<header class="sticky top-0 z-40 border-b border-line bg-paper/90 backdrop-blur-md">
    <div class="wrap flex h-16 items-center gap-4 lg:h-18 lg:gap-8">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="shrink-0 rounded-sm" aria-label="Stiqon – zur Startseite">
            <?php echo stiqon_logo(['class' => 'h-8 w-auto']); ?>
        </a>

        <nav aria-label="Hauptnavigation" class="hidden items-center gap-1 md:flex">
            <?php foreach ($areas as $area) : ?>
                <a
                    href="<?php echo esc_url($area['url']); ?>"<?php echo $area['current'] ? ' aria-current="page"' : ''; ?>
                    class="<?php echo esc_attr(stiqon_classes([$link, 'bg-mist text-ink' => $area['current'], 'text-ink-soft hover:bg-mist hover:text-ink' => ! $area['current']])); ?>"
                ><?php echo esc_html($area['label']); ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="ml-auto flex items-center gap-2">
            <?php echo stiqon_motif_button('Eigenes Motiv', ['variant' => 'secondary', 'size' => 'sm', 'class' => 'max-sm:hidden']); ?>

            <?php // The menu for small screens is a plain disclosure: it opens without any script. ?>
            <details class="group/menu md:hidden">
                <summary class="inline-flex size-11 cursor-pointer list-none items-center justify-center rounded-full text-ink transition-colors hover:bg-mist [&::-webkit-details-marker]:hidden">
                    <span class="sr-only">Menü</span>
                    <?php echo stiqon_icon('menu', 'group-open/menu:hidden'); ?>
                    <?php echo stiqon_icon('x', 'hidden group-open/menu:block'); ?>
                </summary>
                <div class="absolute inset-x-0 top-full border-y border-line bg-paper">
                    <nav aria-label="Hauptnavigation" class="wrap flex flex-col gap-1 py-4">
                        <?php foreach ($areas as $area) : ?>
                            <a
                                href="<?php echo esc_url($area['url']); ?>"<?php echo $area['current'] ? ' aria-current="page"' : ''; ?>
                                class="<?php echo esc_attr(stiqon_classes(['flex h-12 items-center rounded-control px-4 text-lg font-medium', 'bg-mist' => $area['current'], 'hover:bg-mist' => ! $area['current']])); ?>"
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
