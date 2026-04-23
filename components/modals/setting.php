<?php
$tags = get_tags(array(
    'hide_empty' => true,
    'orderby' => 'count',
    'order' => 'DESC',
    'number' => 40,
));

if (count($tags) > 20) {
    shuffle($tags);
    $tags = array_slice($tags, 0, 20);
}
?>
<div class="setting-modal" x-cloak x-data="{settingModalShow: false}" x-show="settingModalShow"
     x-transition.opacity.duration.300ms @click.self="settingModalShow = false">
    <div class="setting-container" x-transition.scale.duration.300ms>
        <div>
            <div class="setting-modal-header">
                <div class="setting-modal-title">设置</div>
                <button type="button" class="setting-modal-close" @click="settingModalShow = false">×</button>
            </div>

            <div class="setting-section">
                <div class="setting-section-title">🔍 搜索</div>
                <div class="search-form">
                    <form id="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" role="search">
                        <input type="text" id="s" name="s" class="text" placeholder="输入关键字搜索" value="<?php echo esc_attr(get_search_query()); ?>">
                        <button type="submit" class="submit"><?php get_template_part('components/svgs/search'); ?></button>
                    </form>
                </div>
            </div>

            <div class="setting-section">
                <div class="setting-section-title">🏷️ 热门标签</div>
                <div class="setting-tags">
                    <?php foreach ($tags as $tag) : ?>
                        <a class="tag" href="<?php echo esc_url(get_tag_link($tag)); ?>"><?php echo esc_html($tag->name); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="setting-section">
                <div class="setting-section-title">🎨 主题模式</div>
                <div class="setting-content">
                    <div class="mode" x-data="{darkMode: document.documentElement.classList.contains('dark')}"
                         @click="darkMode = !darkMode; document.documentElement.classList.toggle('dark', darkMode); localStorage.setItem('timellow_theme_mode', darkMode ? 'dark' : 'light')">
                        <template x-if="!darkMode">
                            <?php get_template_part('components/svgs/moon'); ?>
                        </template>
                        <template x-if="darkMode">
                            <?php get_template_part('components/svgs/sun'); ?>
                        </template>
                    </div>
                </div>
            </div>

            <div class="copyright">
                <div>&copy;  <?php echo esc_html(date('Y')); ?> .
                    <?php bloginfo('name'); ?>
                    All Rights Reserved . Powered by <a href="https://wordpress.org">WordPress</a>
                </div>
            </div>
        </div>
    </div>
</div>
