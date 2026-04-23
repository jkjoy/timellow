<?php get_header(); ?>
<main>
    <?php get_template_part('components/head'); ?>

    <section class="content-container">
        <div class="post-detail" x-data="commentReplyManager()">
            <?php
            if (have_posts()) {
                while (have_posts()) {
                    the_post();
                    timellow_render_post_article(get_post(), array(
                        'detail' => true,
                        'page' => false,
                        'comment_limit' => 20,
                    ));
                }
            }
            ?>
        </div>
    </section>

    <?php get_template_part('components/modals/setting'); ?>
    <?php get_template_part('components/modals/login'); ?>
    <?php get_template_part('components/modals/links'); ?>
</main>
<?php get_footer(); ?>

