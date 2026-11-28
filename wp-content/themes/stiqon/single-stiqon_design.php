<?php
/**
 * One design: its preview large on a grey panel and, beside it, title, medium
 * and occasion (links to the filtered list), the description, and the ways on –
 * try it as the motif on the products, or go to the area of its medium. Below,
 * more designs for the same medium.
 *
 * WordPress only comes here for a design the shop shows; for any other the
 * plugin answers with "not found" (Content\Designs).
 */

defined('ABSPATH') || exit;

stiqon_declare_lean_page();

the_post();

$data = stiqon_design(get_post());
$design = $data['design'];
$area = stiqon_design_area($design['medium']);
// The motif is scaled to 1600 px in the browser anyway: a size the shop keeps of at most that, not the original.
$image = $design['image'] > 0 ? wp_get_attachment_image_url($design['image'], '1536x1536') : false;
// The kinds of picture a motif can be (as for a file the visitor chooses); others get no button.
$type = $design['image'] > 0 ? (string) get_post_mime_type($design['image']) : '';
$motif = $image !== false && in_array($type, ['image/png', 'image/jpeg', 'image/webp'], true);

$terms = array_filter([
    'werbemittel' => $design['medium'],
    'anlass' => $design['occasion'],
]);

get_header();
?>
<div class="wrap pt-4 pb-16 lg:pt-8 lg:pb-28">
    <a href="<?php echo esc_url(stiqon_designs_url()); ?>" class="text-action -ml-1 inline-flex h-11 items-center gap-2 rounded-full px-1 text-muted transition-colors hover:text-ink">
        <?php echo stiqon_icon('arrow-left'); ?>Alle Designs
    </a>

    <article class="mt-4 grid gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:gap-14">
        <div class="aspect-[4/5] rounded-tile bg-mist p-[10%]">
            <?php
            echo wp_get_attachment_image($design['image'], 'full', false, [
                'class' => 'size-full object-contain',
                // The picture is what the page is about; on the cards the title beside it says it.
                'alt' => sprintf('Design „%s“', $design['title']),
                'loading' => 'eager',
                'fetchpriority' => 'high',
                'sizes' => '(min-width: 64rem) 36rem, 100vw',
            ]);
            ?>
        </div>

        <div class="lg:py-4">
            <h1 class="text-title"><?php echo esc_html($design['title']); ?></h1>

            <?php if ($terms !== []) : ?>
                <p class="text-lead mt-3 text-muted">
                    <?php
                    $links = [];

                    foreach ($terms as $filter => $term) {
                        $links[] = '<a href="'.esc_url(stiqon_designs_url([$filter => $term['slug']])).'" class="rounded-sm underline decoration-1 underline-offset-4 transition-colors hover:text-ink">'.esc_html($term['name']).'</a>';
                    }

                    echo implode(' · ', $links); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above
                    ?>
                </p>
            <?php endif; ?>

            <div class="prose mt-6 max-w-none text-ink">
                <?php the_content(); ?>
            </div>

            <div class="mt-8 flex flex-wrap items-center gap-2">
                <?php if ($motif) : ?>
                    <?php // Hands the picture to the visitor's motif, as if they had chosen it as a file (assets/src/js/design.js). ?>
                    <?php // While the picture comes, the label says so – a slow line would otherwise look like a dead button. ?>
                    <span x-data="designMotif" data-needs-script class="inline-flex"<?php echo stiqon_attributes(['data-image' => $image, 'data-type' => $type, 'data-name' => $design['title'], 'data-target' => $area['url']]); ?>>
                        <?php echo stiqon_button(stiqon_icon('image').'<span x-text="busy ? \'Wird geladen …\' : \'Als Motiv ausprobieren\'">Als Motiv ausprobieren</span>', ['attributes' => ['x-on:click' => 'tryOn', 'x-bind:aria-disabled' => 'busy', 'x-bind:aria-busy' => 'busy', 'data-motif-button' => true]]); ?>
                    </span>
                <?php endif; ?>
                <?php echo stiqon_button(esc_html($area['label']).stiqon_icon('arrow-right'), ['variant' => 'secondary', 'href' => $area['url']]); ?>
            </div>
            <?php if ($motif) : ?>
                <p class="text-label mt-3 text-muted" data-needs-script>Das Design erscheint auf den Ansichten der Produkte. Hochgeladen wird nichts.</p>
            <?php endif; ?>
        </div>
    </article>

    <?php if ($data['beside'] !== []) : ?>
        <section class="mt-16 lg:mt-24" aria-labelledby="weitere-designs">
            <h2 id="weitere-designs" class="text-title">Weitere Designs</h2>
            <ul class="mt-8 grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-4 lg:grid-cols-3 lg:gap-x-5">
                <?php foreach ($data['beside'] as $other) : ?>
                    <?php echo stiqon_design_card($other); ?>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
</div>
<?php
get_footer();
