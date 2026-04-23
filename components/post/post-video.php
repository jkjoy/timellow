<?php
$video_url = isset($args['video_url']) ? $args['video_url'] : '';
if ($video_url === '') {
    return;
}
?>
<div class="post-video">
    <video controls controlsList="nodownload" preload="metadata">
        <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
        您的浏览器不支持视频播放
    </video>
</div>
