<?php
$images = isset($args['images']) && is_array($args['images']) ? $args['images'] : array();
$post_id = isset($args['post_id']) ? (int) $args['post_id'] : get_the_ID();
$image_count = count($images);

if ($image_count === 0) {
    return;
}

if ($image_count === 1) :
    ?>
    <div class="post-images">
        <div class="post-images-1 grid">
            <?php foreach ($images as $image) : ?>
                <figure class="cell post-image-one">
                    <img src="<?php echo esc_url($image); ?>" alt="" class="preview-image"
                         data-fancybox="<?php echo esc_attr($post_id); ?>" data-src="<?php echo esc_url($image); ?>">
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
<?php elseif ($image_count === 4) : ?>
    <div class="post-images fixed-grid has-3-cols">
        <div class="post-images-2 grid">
            <?php $index = 1; ?>
            <?php foreach ($images as $image) : ?>
                <figure class="cell post-image">
                    <img src="<?php echo esc_url($image); ?>" alt="" class="preview-image"
                         data-fancybox="<?php echo esc_attr($post_id); ?>" data-src="<?php echo esc_url($image); ?>">
                </figure>
                <?php if ($index === 2) : ?>
                    <figure></figure>
                <?php endif; ?>
                <?php $index++; ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php elseif ($image_count > 9) : ?>
    <div class="post-images fixed-grid has-3-cols">
        <div class="post-images-container grid">
            <?php $index = 1; ?>
            <?php foreach ($images as $image) : ?>
                <?php $hidden = $index > 9 ? 'hidden' : ''; ?>
                <?php if ($index === 9) : ?>
                    <figure class="cell post-image <?php echo esc_attr($hidden); ?> reactive">
                        <img src="<?php echo esc_url($image); ?>" alt="">
                        <div class="post-image-zzc preview-image"
                             data-fancybox="<?php echo esc_attr($post_id); ?>"
                             data-src="<?php echo esc_url($image); ?>">+<?php echo esc_html($image_count - 9); ?></div>
                    </figure>
                <?php else : ?>
                    <figure class="cell post-image <?php echo esc_attr($hidden); ?>">
                        <img src="<?php echo esc_url($image); ?>" alt="" class="preview-image"
                             data-fancybox="<?php echo esc_attr($post_id); ?>" data-src="<?php echo esc_url($image); ?>">
                    </figure>
                <?php endif; ?>
                <?php $index++; ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php else : ?>
    <div class="post-images fixed-grid has-3-cols">
        <div class="post-images-2 grid">
            <?php foreach ($images as $image) : ?>
                <figure class="cell post-image">
                    <img src="<?php echo esc_url($image); ?>" alt="" class="preview-image"
                         data-fancybox="<?php echo esc_attr($post_id); ?>" data-src="<?php echo esc_url($image); ?>">
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
