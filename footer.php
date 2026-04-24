<?php
if (!defined('ABSPATH')) {
    exit;
}

$beian_info = (string) timellow_get_option('beian_info');
?>
<?php get_template_part('components/modals/write'); ?>
<?php get_template_part('components/modals/dialog'); ?>
<?php if ($beian_info !== '') : ?>
    <footer class="site-footer">
        <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer"><?php echo esc_html($beian_info); ?></a>
    </footer>
<?php endif; ?>
<div class="back-to-top" id="backToTop">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="18 15 12 9 6 15"></polyline>
    </svg>
</div>

<?php wp_footer(); ?>
</body>
</html>
