<?php
/**
 * Closes every page: the dark footer and the bar that reports the visitor's motif.
 */

defined('ABSPATH') || exit;
?>
</main>

<footer class="on-night border-t border-gold/70 bg-night text-on-night">
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
                <p class="mt-6 max-w-sm text-on-night-muted">
                    Sticker und Flyer mit Ihrem Motiv – erst ansehen, dann bestellen.
                </p>
            </div>

            <nav aria-labelledby="footer-areas">
                <h2 id="footer-areas" class="text-overline text-on-night-muted">Bereiche</h2>
                <ul class="mt-4 space-y-1">
                    <?php foreach (stiqon_areas() as $area) : ?>
                        <li><a href="<?php echo esc_url($area['url']); ?>" class="inline-flex h-10 items-center rounded-sm hover:underline"><?php echo esc_html($area['label']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>

        <div class="mt-14 flex flex-col gap-2 border-t border-on-night/15 pt-6 text-sm text-on-night-muted sm:flex-row sm:justify-between">
            <p>© <?php echo esc_html(wp_date('Y')); ?> Stiqon</p>
            <p>Vorschau – der Shop ist noch im Aufbau.</p>
        </div>
    </div>
</footer>

<?php get_template_part('template-parts/motif-status'); ?>

<?php wp_footer(); ?>
</body>
</html>
