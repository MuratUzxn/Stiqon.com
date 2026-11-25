<?php
/**
 * Closes every page: the dark footer, the bar that reports the visitor's motif,
 * and the search that opens over the page.
 */

defined('ABSPATH') || exit;
?>
</main>

<footer class="on-night bg-night text-on-night">
    <?php // The page above ends in rounded corners that sit on the dark footer. ?>
    <div class="h-8 rounded-b-tile bg-paper" aria-hidden="true"></div>

    <div class="wrap py-14 lg:py-20">
        <div class="grid gap-12 md:grid-cols-[1.6fr_1fr]">
            <div>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-block rounded-sm" aria-label="Stiqon – zur Startseite">
                    <?php echo stiqon_logo([
                        'variant' => 'light',
                        'class' => 'h-10 w-auto lg:h-12',
                        'sizes' => '(min-width: 64rem) 156px, 130px',
                        'attributes' => ['loading' => 'lazy'],
                    ]); ?>
                </a>
                <p class="text-lead mt-6 max-w-sm text-on-night-muted">
                    Sticker und Flyer mit Ihrem Motiv – erst ansehen, dann bestellen.
                </p>
            </div>

            <nav aria-labelledby="footer-areas">
                <h2 id="footer-areas" class="text-label text-on-night-muted">Übersicht</h2>
                <ul class="mt-3">
                    <?php foreach (stiqon_navigation() as $area) : ?>
                        <li><a href="<?php echo esc_url($area['url']); ?>" class="text-action inline-flex h-11 items-center rounded-sm transition-opacity hover:opacity-60"><?php echo esc_html($area['label']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>

        <div class="text-label mt-14 flex flex-col gap-2 border-t border-white/14 pt-6 text-on-night-muted sm:flex-row sm:justify-between">
            <p>© <?php echo esc_html(wp_date('Y')); ?> Stiqon</p>
            <p>Vorschau – der Shop ist noch im Aufbau.</p>
        </div>
    </div>
</footer>

<?php get_template_part('template-parts/motif-status'); ?>
<?php get_template_part('template-parts/search-panel'); ?>

<?php wp_footer(); ?>
</body>
</html>
