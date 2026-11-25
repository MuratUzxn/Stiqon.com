<?php
/**
 * A page: one written in the editor – legal texts, for instance – or one that
 * WooCommerce draws: cart, checkout, account. A written page gets the type
 * styles for running text. WooCommerce's pages bring markup of their own and
 * are styled by assets/src/css/shop.css.
 */

defined('ABSPATH') || exit;

$shop = stiqon_is_woocommerce_page();

if ($shop) {
    stiqon_declare_shop_page();
}

get_header();
?>
<div class="wrap pt-8 pb-16 lg:pt-14 lg:pb-28">
    <?php while (have_posts()) : ?>
        <?php the_post(); ?>
        <article <?php post_class(); ?>>
            <h1 class="text-title"><?php the_title(); ?></h1>
            <div class="<?php echo esc_attr($shop ? 'mt-8 lg:mt-10' : 'prose mt-8 max-w-3xl'); ?>">
                <?php the_content(); ?>
            </div>
        </article>
    <?php endwhile; ?>
</div>
<?php
get_footer();
