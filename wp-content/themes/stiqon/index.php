<?php
/**
 * Fallback template: pages and anything without a template of its own.
 */

defined('ABSPATH') || exit;

get_header();
?>
<div class="wrap py-10 lg:py-16">
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class(); ?>>
                <h1 class="text-title"><?php the_title(); ?></h1>
                <div class="prose mt-8 max-w-3xl">
                    <?php the_content(); ?>
                </div>
            </article>
        <?php endwhile; ?>
    <?php else : ?>
        <h1 class="text-title">Hier ist noch nichts zu sehen.</h1>
    <?php endif; ?>
</div>
<?php
get_footer();
