<?php get_header(); ?>
<main>
    <?php get_template_part('components/head'); ?>

    <section class="content-container">
        <div class="archive-header">
            <h2 class="archive-title"><?php echo esc_html(timellow_get_archive_heading()); ?></h2>
        </div>

        <?php get_template_part('components/post-list'); ?>
    </section>

    <?php get_template_part('components/modals/setting'); ?>
    <?php get_template_part('components/modals/login'); ?>
    <?php get_template_part('components/modals/links'); ?>
</main>
<?php get_footer(); ?>

