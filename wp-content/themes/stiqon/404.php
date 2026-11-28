<?php
/**
 * An unknown address: what happened, in one sentence, and where to go next.
 */

defined('ABSPATH') || exit;

stiqon_declare_lean_page();
get_header();
?>
<section class="wrap py-20 text-center lg:py-32">
    <p class="text-label text-muted">Fehler 404</p>
    <h1 class="text-title mt-4">Diese Seite gibt es nicht.</h1>
    <p class="text-lead mx-auto mt-5 max-w-md text-muted">Vielleicht wurde sie verschoben, oder in der Adresse steckt ein Tippfehler.</p>
    <div class="mt-9 flex flex-wrap items-center justify-center gap-2">
        <?php echo stiqon_button('Zur Startseite', ['href' => home_url('/')]); ?>
        <?php echo stiqon_button('Sticker ansehen', ['variant' => 'secondary', 'href' => stiqon_page_url('sticker')]); ?>
        <?php echo stiqon_button('Flyer ansehen', ['variant' => 'secondary', 'href' => stiqon_page_url('flyer')]); ?>
    </div>
</section>
<?php
get_footer();
