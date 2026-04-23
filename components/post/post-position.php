<?php
$post_id = isset($args['post_id']) ? (int) $args['post_id'] : get_the_ID();
$position_text = timellow_get_post_position($post_id);
$position_url = timellow_get_post_position_url($post_id);

if ($position_text === '') {
    return;
}
?>
<div class="post-position">
    <?php if ($position_url !== '') : ?>
        <a href="<?php echo esc_url($position_url); ?>" target="_blank"><?php echo esc_html($position_text); ?></a>
    <?php else : ?>
        <span><?php echo esc_html($position_text); ?></span>
    <?php endif; ?>
</div>
