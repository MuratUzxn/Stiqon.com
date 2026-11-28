<?php
/**
 * The designs, laid out like mobbin.com's browse page: a centred title, an
 * explore block of large links (by medium and by occasion), a toolbar with a
 * segmented control for the medium and text tabs for the occasion, then a grid
 * of large grey cards. Every filter is a link with a query argument
 * (?werbemittel=flyer&anlass=feier), so filtering works without script; the
 * plugin narrows the list to match (Content\Designs).
 */

defined('ABSPATH') || exit;

stiqon_declare_lean_page();

global $wp_query;

$data = stiqon_designs();
$selection = $data['selection'];
$page = max(1, (int) get_query_var('paged'));
$pages = (int) $wp_query->max_num_pages;
$found = (int) $wp_query->found_posts;

/** Address of the list with one filter changed; null takes the filter away. */
$with = static fn (string $filter, ?string $value): string => stiqon_designs_url([$filter => $value] + $selection);

get_header();
?>
<section class="wrap pt-8 pb-10 text-center lg:pt-14 lg:pb-14">
    <h1 class="text-display">Designs</h1>
    <p class="text-lead mx-auto mt-6 max-w-xl text-muted">Vorlagen für Ihre Werbemittel. Probieren Sie jedes Design auf unseren Produkten aus.</p>
</section>

<?php if (! $data['any']) : ?>
    <section class="wrap pb-16 lg:pb-28">
        <p class="mx-auto max-w-xl rounded-card bg-mist px-6 py-5 text-center text-muted">
            Die ersten Designs entstehen gerade.
            <a href="<?php echo esc_url(home_url('/')); ?>" class="font-semibold text-ink underline underline-offset-4">Zu den Produkten</a>
        </p>
    </section>
<?php else : ?>
    <?php // The model's explore block: a small grey label over each column, then large links. ?>
    <?php // Only on the list's start, as on the model's landing: below, the toolbar says the same and keeps the other filter. ?>
    <?php if (array_filter($selection) === [] && $page === 1) : ?>
        <nav aria-label="Designs entdecken" class="wrap grid grid-cols-2 gap-x-6 gap-y-10 pb-12 sm:grid-cols-[repeat(2,minmax(0,18rem))] lg:pb-16">
            <?php foreach ([['werbemittel', 'Werbemittel', $data['media']], ['anlass', 'Anlässe', $data['occasions']]] as [$filter, $label, $terms]) : ?>
                <?php if ($terms !== []) : ?>
                    <div>
                        <h2 class="text-label text-muted"><?php echo esc_html($label); ?></h2>
                        <ul class="mt-2">
                            <?php foreach ($terms as $term) : ?>
                                <li>
                                    <?php // 44 px tall where a finger taps; with a mouse the model's tight column. ?>
                                    <a
                                        href="<?php echo esc_url($with($filter, $term['slug'])); ?>"
                                        class="inline-flex min-h-11 items-center rounded-sm text-[1.375rem] leading-tight font-medium tracking-[-0.005em] transition-colors duration-200 ease-soft hover:text-muted lg:min-h-0 lg:py-0.5 lg:text-[1.625rem]"
                                    ><?php echo esc_html($term['name']); ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <section class="wrap pb-16 lg:pb-28" aria-labelledby="alle-designs">
        <h2 id="alle-designs" class="sr-only">Alle Designs</h2>

        <?php
        $media = [['label' => 'Alle', 'url' => $with('werbemittel', null), 'current' => $selection['werbemittel'] === null]];

        foreach ($data['media'] as $term) {
            $media[] = ['label' => $term['name'], 'url' => $with('werbemittel', $term['slug']), 'current' => $selection['werbemittel'] === $term['slug']];
        }

        $occasions = [['label' => 'Alle Anlässe', 'url' => $with('anlass', null), 'current' => $selection['anlass'] === null]];

        foreach ($data['occasions'] as $term) {
            $occasions[] = ['label' => $term['name'], 'url' => $with('anlass', $term['slug']), 'current' => $selection['anlass'] === $term['slug']];
        }
        ?>
        <?php // One row, as on the model. On a phone the occasions get a row of their own and wrap: every filter, and the chosen one above all, stays in sight. ?>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
            <nav aria-label="Designs filtern" class="flex min-w-0 flex-1 flex-col items-start gap-1 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-4">
                <?php echo stiqon_segments($media, 'Werbemittel', '', false); ?>
                <span class="h-6 w-px shrink-0 bg-line max-sm:hidden" aria-hidden="true"></span>
                <?php echo stiqon_tabs($occasions, 'Anlass', '', false); ?>
            </nav>
            <p class="text-label shrink-0 text-muted">
                <?php echo esc_html(sprintf($found === 1 ? '%d Design' : '%d Designs', $found)); ?>
            </p>
        </div>

        <?php if ($data['designs'] === []) : ?>
            <p class="mx-auto mt-10 max-w-xl rounded-card bg-mist px-6 py-5 text-center text-muted">
                Zu dieser Auswahl gibt es noch kein Design.
                <a href="<?php echo esc_url(stiqon_designs_url()); ?>" class="font-semibold text-ink underline underline-offset-4">Alle Designs</a>
            </p>
        <?php else : ?>
            <ul class="mt-6 grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-4 lg:mt-8 lg:grid-cols-3 lg:gap-x-5 lg:gap-y-10">
                <?php foreach ($data['designs'] as $index => $design) : ?>
                    <?php echo stiqon_design_card($design, $index < 3); ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($pages > 1) : ?>
            <nav aria-label="Seiten" class="mt-12 flex flex-wrap items-center justify-center gap-3">
                <?php if ($page > 1) : ?>
                    <?php echo stiqon_button(stiqon_icon('arrow-left').'Vorherige Seite', ['variant' => 'secondary', 'href' => get_pagenum_link($page - 1), 'attributes' => ['rel' => 'prev']]); ?>
                <?php endif; ?>
                <p class="text-label text-muted"><?php echo esc_html(sprintf('Seite %d von %d', $page, $pages)); ?></p>
                <?php if ($page < $pages) : ?>
                    <?php echo stiqon_button('Nächste Seite'.stiqon_icon('arrow-right'), ['variant' => 'secondary', 'href' => get_pagenum_link($page + 1), 'attributes' => ['rel' => 'next']]); ?>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

        <?php if ($data['examples']) : ?>
            <p class="text-label mx-auto mt-12 max-w-xl text-center text-muted">
                Beispieldesigns für die Entwicklung. Die echten Designs erscheinen, sobald sie geprüft und freigegeben sind.
            </p>
        <?php endif; ?>
    </section>
<?php endif; ?>
<?php
get_footer();
