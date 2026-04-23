<?php
$top_video = timellow_get_media_url('top_video');
$top_image = timellow_get_media_url('top_image');
$avatar_link = timellow_get_option('avatar_link');
$site_avatar = timellow_get_site_avatar_data(96);
?>
<section class="top-container">
    <div class="top-container-left">
        <div class="tc-user" data-icon="user"
             @click="$nextTick(() => { document.querySelector('.login-modal')._x_dataStack[0].loginModalShow = true })">
            <?php get_template_part('components/svgs/user'); ?>
        </div>
        <div class="tc-links" data-icon="links"
             @click="$nextTick(() => { window.dispatchEvent(new CustomEvent('links-modal-open')) })">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="24" height="24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
            </svg>
        </div>
    </div>
    <div class="top-container-right">
        <?php if (current_user_can('edit_posts')) : ?>
            <div class="tc-edit" data-icon="edit"
                 @click="$nextTick(() => { window.dispatchEvent(new CustomEvent('write-modal-open')) })">
                <?php get_template_part('components/svgs/edit'); ?>
            </div>
        <?php endif; ?>
        <div class="tc-setting" data-icon="setting"
             @click="$nextTick(() => { document.querySelector('.setting-modal')._x_dataStack[0].settingModalShow = true })">
            <?php get_template_part('components/svgs/setting'); ?>
        </div>
    </div>
</section>

<div class="preloaded-icons" style="display: none;">
    <div data-icon="user"><?php get_template_part('components/svgs/user'); ?></div>
    <div data-icon="music"><?php get_template_part('components/svgs/music'); ?></div>
    <div data-icon="edit"><?php get_template_part('components/svgs/edit'); ?></div>
    <div data-icon="setting"><?php get_template_part('components/svgs/setting'); ?></div>
    <div data-icon="user-outline"><?php get_template_part('components/svgs/user-outline'); ?></div>
    <div data-icon="music-outline"><?php get_template_part('components/svgs/music-outline'); ?></div>
    <div data-icon="edit-outline"><?php get_template_part('components/svgs/edit-outline'); ?></div>
    <div data-icon="setting-outline"><?php get_template_part('components/svgs/setting-outline'); ?></div>
</div>

<section class="header-container" style="<?php
if ($top_video === '' && $top_image === '') {
    echo 'background-color: #f1f1f1;';
} elseif ($top_video === '' && $top_image !== '') {
    echo 'background-image: url(' . esc_url($top_image) . ');';
}
?>">
    <?php if ($top_video !== '') : ?>
        <video src="<?php echo esc_url($top_video); ?>" autoplay muted loop playsinline></video>
    <?php endif; ?>

    <div class="header-info">
        <div class="header-user">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="header-site-title">
                <span><?php bloginfo('name'); ?></span>
            </a>
            <?php if ($avatar_link) : ?>
                <a href="<?php echo esc_url($avatar_link); ?>" class="header-avatar-link">
                    <img src="<?php echo timellow_escape_img_src($site_avatar['url']); ?>" alt="<?php echo esc_attr($site_avatar['name']); ?>">
                </a>
            <?php else : ?>
                <div class="header-avatar-nolink">
                    <img src="<?php echo timellow_escape_img_src($site_avatar['url']); ?>" alt="<?php echo esc_attr($site_avatar['name']); ?>">
                </div>
            <?php endif; ?>
        </div>
        <div class="header-description"><?php bloginfo('description'); ?></div>
    </div>
</section>
