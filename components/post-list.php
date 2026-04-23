<div class="scrollload-container">
    <div class="post-list scrollload-content" x-data="commentReplyManager()">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <?php timellow_render_post_article(get_post(), array('detail' => false, 'comment_limit' => 5)); ?>
            <?php endwhile; ?>
        <?php else : ?>
            <div class="archive-empty">
                <div class="archive-empty-icon">🔍</div>
                <h3>没有找到内容</h3>
                <p>换个关键词试试吧</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php timellow_render_pagination_state(); ?>
