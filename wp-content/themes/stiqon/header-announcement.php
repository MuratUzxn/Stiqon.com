<?php
/**
 * Opens a page while the whole site is announced ("Stiqon öffnet bald."):
 * the document head and the logo, nothing else. Imprint and privacy policy stay
 * readable in that time (inc/announcement.php), and they stand in this frame –
 * the shop's own header would offer areas, a search and a cart that all lead
 * back to the announcement.
 */

defined('ABSPATH') || exit;
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

<header class="wrap py-6">
    <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-block rounded-sm" aria-label="Stiqon – zur Startseite">
        <?php echo stiqon_logo(['class' => 'h-8 w-auto']); ?>
    </a>
</header>

<main id="inhalt" class="flex-1">
