<?php
/**
 * The references: work the shop has delivered, as a grid of photos with a
 * name and a sentence each. WordPress only comes here when there is at least
 * one – without, the plugin answers this address with "not found".
 */

defined('ABSPATH') || exit;

stiqon_declare_lean_page();

$references = stiqon_references();

get_header();
?>
<section class="wrap pt-8 pb-10 text-center lg:pt-14 lg:pb-12">
    <nav aria-label="Pfad" class="text-label text-muted">
        <ol class="flex items-center justify-center gap-1.5">
            <li><a href="<?php echo esc_url(home_url('/')); ?>" class="rounded-sm transition-colors hover:text-ink">Start</a></li>
            <li aria-hidden="true">›</li>
            <li aria-current="page" class="text-ink">Referenzen</li>
        </ol>
    </nav>
    <h1 class="text-display mt-4">Referenzen</h1>
    <p class="text-lead mx-auto mt-6 max-w-xl text-muted">Werbemittel, die wir gedruckt haben – und was aus ihnen geworden ist.</p>
</section>

<section class="wrap pb-16 lg:pb-28" aria-labelledby="arbeiten">
    <h2 id="arbeiten" class="sr-only">Alle Referenzen</h2>

    <ul class="flex flex-wrap justify-center gap-x-4 gap-y-10">
        <?php foreach ($references as $reference) : ?>
            <?php echo stiqon_reference_card($reference); ?>
        <?php endforeach; ?>
    </ul>
</section>
<?php
get_footer();
