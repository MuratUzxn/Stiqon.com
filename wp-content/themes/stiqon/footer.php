<?php
/**
 * Closes every page.
 */

defined('ABSPATH') || exit;
?>
</main>

<footer class="on-night bg-night text-on-night">
    <div class="page py-10 text-sm text-on-night-muted">
        <p>© <?php echo esc_html(wp_date('Y')); ?> <?php bloginfo('name'); ?></p>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
