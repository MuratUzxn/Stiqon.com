<?php
/**
 * Closes a page while the whole site is announced: the foot of the
 * announcement, see header-announcement.php.
 */

defined('ABSPATH') || exit;
?>
</main>

<footer class="wrap text-label flex flex-wrap justify-center gap-x-6 gap-y-2 py-8 text-muted">
    <?php echo stiqon_announcement_footer(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped where it is built ?>
</footer>

<?php wp_footer(); ?>
</body>
</html>
