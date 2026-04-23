<?php get_header(); ?>
<main>
    <?php get_template_part('components/head'); ?>

    <section class="content-container">
        <?php get_template_part('components/post-list'); ?>
    </section>

    <?php get_template_part('components/modals/setting'); ?>
    <?php get_template_part('components/modals/login'); ?>
    <?php get_template_part('components/modals/links'); ?>
</main>
<?php get_footer(); ?>

