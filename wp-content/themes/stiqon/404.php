<?php
/**
 * An unknown address: what happened, in one sentence, and where to go next.
 */

defined('ABSPATH') || exit;

get_header();
?>
<section class="wrap py-20 lg:py-32">
    <p class="text-overline text-muted">Fehler 404</p>
    <h1 class="text-title mt-5 max-w-3xl">Diese Seite gibt es nicht.</h1>
    <p class="text-lead mt-5 max-w-xl text-ink-soft">Vielleicht wurde sie verschoben, oder in der Adresse steckt ein Tippfehler.</p>
    <div class="mt-9 flex flex-wrap items-center gap-3">
        <?php echo stiqon_button('Zur Startseite', ['size' => 'lg', 'href' => home_url('/')]); ?>
        <?php echo stiqon_button('Sticker ansehen', ['variant' => 'secondary', 'size' => 'lg', 'href' => home_url('/sticker/')]); ?>
        <?php echo stiqon_button('Flyer ansehen', ['variant' => 'secondary', 'size' => 'lg', 'href' => home_url('/flyer/')]); ?>
    </div>
</section>
<?php
get_footer();
