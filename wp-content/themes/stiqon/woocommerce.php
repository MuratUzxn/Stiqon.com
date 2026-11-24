<?php
/**
 * Frame for all shop pages until they get templates of their own.
 */

defined('ABSPATH') || exit;

get_header();
?>
<div class="page py-12 lg:py-16">
    <?php woocommerce_content(); ?>
</div>
<?php
get_footer();
