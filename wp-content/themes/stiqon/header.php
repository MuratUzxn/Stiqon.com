<?php
/**
 * Opens every page. The full header (logo, navigation, motif button) follows
 * with the transfer of the design; this is the frame it will sit in.
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
    class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-full focus:bg-ink focus:px-5 focus:py-3 focus:text-paper"
>Zum Inhalt springen</a>

<header class="border-b border-line">
    <div class="page flex h-16 items-center">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="text-heading"><?php bloginfo('name'); ?></a>
    </div>
</header>

<main id="inhalt" class="flex-1">
