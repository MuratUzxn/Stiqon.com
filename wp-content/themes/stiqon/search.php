<?php
/**
 * What the shop's search found: a field with the query, the areas that fit as
 * links, and the products as cards – the same cards the galleries show. A
 * product stands for all its variants unless one of them was asked for by
 * name ("kraftpapier").
 */

defined('ABSPATH') || exit;

stiqon_declare_lean_page();

$query = stiqon_search_query();
$found = stiqon_search($query);
$listed = count($found['areas']) + count($found['cards']);

get_header();
?>
<section class="wrap pt-8 pb-10 text-center lg:pt-14 lg:pb-12">
    <nav aria-label="Pfad" class="text-label text-muted">
        <ol class="flex items-center justify-center gap-1.5">
            <li><a href="<?php echo esc_url(home_url('/')); ?>" class="rounded-sm transition-colors hover:text-ink">Start</a></li>
            <li aria-hidden="true">›</li>
            <li aria-current="page" class="text-ink">Suche</li>
        </ol>
    </nav>
    <h1 class="text-title mt-4">Suche</h1>
    <?php echo stiqon_search_form($query, 'mx-auto mt-8 max-w-xl'); ?>
</section>

<section class="wrap pb-16 lg:pb-28" aria-labelledby="treffer">
    <h2 id="treffer" class="sr-only">Treffer</h2>

    <?php if ($query !== '' && $found['total'] > 0) : ?>
        <p class="text-label text-center text-muted">
            <?php echo esc_html(sprintf('%d Treffer für „%s“', $found['total'], $query)); ?>
            <?php echo $found['cards'] !== [] ? '· Preise inkl. MwSt., zzgl. Versand' : ''; ?>
        </p>

        <?php if ($found['areas'] !== []) : ?>
            <ul class="mt-6 flex flex-wrap items-center justify-center gap-2">
                <?php foreach ($found['areas'] as $area) : ?>
                    <li>
                        <?php echo stiqon_button(
                            esc_html('Bereich '.$area['name']).' <span class="font-normal">'.esc_html($area['note']).'</span>'.stiqon_icon('arrow-right'),
                            ['variant' => 'secondary', 'href' => $area['url']],
                        ); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($found['cards'] !== []) : ?>
            <ul class="mt-8 grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-4 md:grid-cols-3 lg:mt-10 lg:grid-cols-4">
                <?php foreach ($found['cards'] as $card) : ?>
                    <?php echo stiqon_product_card($card); ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($found['total'] > $listed) : ?>
            <p class="text-label mx-auto mt-12 max-w-xl text-center text-muted">
                <?php echo esc_html(sprintf('Angezeigt sind die ersten %d Treffer. Mit einem weiteren Wort wird die Suche genauer.', $listed)); ?>
            </p>
        <?php endif; ?>
    <?php else : ?>
        <div class="mx-auto max-w-xl rounded-card bg-mist px-6 py-6 text-center">
            <?php if ($query === '') : ?>
                <p class="font-semibold">Wonach suchen Sie?</p>
                <p class="mt-1 text-muted">Zum Beispiel nach einer Form, einem Format oder einem Material: „rund“, „DIN A5“, „Kraftpapier“.</p>
            <?php else : ?>
                <p class="font-semibold"><?php echo esc_html(sprintf('Zu „%s“ haben wir nichts gefunden.', $query)); ?></p>
                <p class="mt-1 text-muted">Versuchen Sie es mit einem anderen Wort – etwa einer Form, einem Format oder einem Material.</p>
            <?php endif; ?>
            <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                <?php foreach (stiqon_areas() as $area) : ?>
                    <?php echo stiqon_button(esc_html('Alle '.$area['label']), ['variant' => 'secondary', 'href' => $area['url']]); ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
<?php
get_footer();
