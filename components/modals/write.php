<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<script>
function timellowWriteModalManager() {
    return {
        writeModalShow: false,
        editingPostId: 0,
        postContent: '',
        mediaFiles: [],
        position: '',
        tencentGeocoder: null,
        visibility: 'public',
        isAdvertise: false,
        isSticky: false,
        locationLoading: false,
        showVisibilityPicker: false,
        submitStatus: '',
        submitting: false,

        get isEditing() {
            return this.editingPostId > 0;
        },

        get modalTitle() {
            return this.isEditing ? '编辑文章' : '撰写';
        },

        get submitButtonText() {
            if (this.submitting) {
                return this.isEditing ? '保存中...' : '发布中...';
            }

            return this.isEditing ? '保存' : '发表';
        },

        get visibilityText() {
            return this.visibility === 'private' ? '私密' : '公开';
        },

        get hasVideoMedia() {
            return this.mediaFiles.some((item) => item.mediaType === 'video');
        },

        open(payload = null) {
            this.resetForm();

            if (payload && payload.mode === 'edit' && payload.post) {
                this.applyEditorPost(payload.post);
            }

            this.writeModalShow = true;

            setTimeout(() => {
                const textarea = this.$refs.postContent;
                if (textarea) {
                    textarea.focus();
                    this.autoResize({ target: textarea });
                }
            }, 60);
        },

        close() {
            if (this.submitting) {
                return;
            }

            this.writeModalShow = false;
            this.showVisibilityPicker = false;
            this.submitStatus = '';
        },

        resetForm() {
            this.editingPostId = 0;
            this.postContent = '';
            this.mediaFiles = [];
            this.position = '';
            this.visibility = 'public';
            this.isAdvertise = false;
            this.isSticky = false;
            this.showVisibilityPicker = false;
            this.submitStatus = '';
            this.submitting = false;

            if (this.$refs.postContent) {
                this.$refs.postContent.style.height = 'auto';
            }
        },

        applyEditorPost(post) {
            this.editingPostId = parseInt(post.postId, 10) || 0;
            this.postContent = typeof post.content === 'string' ? post.content : '';
            this.position = typeof post.position === 'string' ? post.position : '';
            this.visibility = post.visibility === 'private' ? 'private' : 'public';
            this.isAdvertise = !!post.isAdvertise;
            this.isSticky = this.visibility === 'public' && !!post.isSticky;
            this.mediaFiles = Array.isArray(post.mediaFiles)
                ? post.mediaFiles
                    .map((item) => ({
                        id: parseInt(item.id, 10) || 0,
                        mediaType: item.mediaType === 'video' ? 'video' : 'image',
                        type: item.type || '',
                        url: item.url || '',
                        preview: item.preview || item.url || ''
                    }))
                    .filter((item) => item.id > 0 && item.url)
                : [];
        },

        autoResize(event) {
            const textarea = event.target;
            if (!textarea) {
                return;
            }

            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        },

        resolveMediaType(attachment) {
            if (attachment && (attachment.type === 'image' || attachment.type === 'video')) {
                return attachment.type;
            }

            const mimeType = attachment && attachment.mime ? String(attachment.mime) : '';

            if (mimeType.startsWith('image/')) {
                return 'image';
            }

            if (mimeType.startsWith('video/')) {
                return 'video';
            }

            return '';
        },

        normalizeMediaAttachment(attachment) {
            const mediaType = this.resolveMediaType(attachment);

            if (!attachment || !attachment.id || mediaType === '' || !attachment.url) {
                return null;
            }

            let previewUrl = attachment.url;

            if (mediaType === 'image' && attachment.sizes) {
                previewUrl = attachment.sizes.medium?.url || attachment.sizes.thumbnail?.url || attachment.url;
            }

            return {
                id: parseInt(attachment.id, 10),
                mediaType: mediaType,
                type: attachment.mime || '',
                url: attachment.url,
                preview: previewUrl
            };
        },

        applyMediaSelection(attachments) {
            const normalized = (attachments || [])
                .map((attachment) => this.normalizeMediaAttachment(attachment))
                .filter(Boolean);

            const images = normalized.filter((item) => item.mediaType === 'image');
            const videos = normalized.filter((item) => item.mediaType === 'video');

            if (videos.length > 1) {
                alert('只能选择 1 个视频');
                return;
            }

            if (videos.length > 0 && images.length > 0) {
                alert('视频不能和图片同时选择');
                return;
            }

            if (images.length > 9) {
                alert('最多只能选择 9 张图片');
                return;
            }

            this.mediaFiles = videos.length > 0 ? [videos[0]] : images;
        },

        openMediaLibrary() {
            if (typeof window.wp === 'undefined' || !window.wp.media) {
                alert('媒体库加载失败，请刷新页面后重试');
                return;
            }

            const frame = window.wp.media({
                title: '选择图片或视频',
                button: {
                    text: '使用所选媒体'
                },
                library: {
                    type: ['image', 'video']
                },
                multiple: 'add'
            });

            frame.on('open', () => {
                const selection = frame.state().get('selection');

                this.mediaFiles.forEach((media) => {
                    if (!media.id) {
                        return;
                    }

                    const attachment = window.wp.media.attachment(media.id);
                    attachment.fetch();
                    selection.add(attachment);
                });
            });

            frame.on('select', () => {
                const attachments = frame.state().get('selection').toJSON();
                this.applyMediaSelection(attachments);
            });

            frame.open();
        },

        removeMedia(index) {
            this.mediaFiles.splice(index, 1);
        },

        isOutOfChina(lat, lng) {
            return lng < 72.004 || lng > 137.8347 || lat < 0.8293 || lat > 55.8271;
        },

        transformLat(x, y) {
            let ret = -100.0 + 2.0 * x + 3.0 * y + 0.2 * y * y + 0.1 * x * y + 0.2 * Math.sqrt(Math.abs(x));
            ret += (20.0 * Math.sin(6.0 * x * Math.PI) + 20.0 * Math.sin(2.0 * x * Math.PI)) * 2.0 / 3.0;
            ret += (20.0 * Math.sin(y * Math.PI) + 40.0 * Math.sin(y / 3.0 * Math.PI)) * 2.0 / 3.0;
            ret += (160.0 * Math.sin(y / 12.0 * Math.PI) + 320 * Math.sin(y * Math.PI / 30.0)) * 2.0 / 3.0;
            return ret;
        },

        transformLng(x, y) {
            let ret = 300.0 + x + 2.0 * y + 0.1 * x * x + 0.1 * x * y + 0.1 * Math.sqrt(Math.abs(x));
            ret += (20.0 * Math.sin(6.0 * x * Math.PI) + 20.0 * Math.sin(2.0 * x * Math.PI)) * 2.0 / 3.0;
            ret += (20.0 * Math.sin(x * Math.PI) + 40.0 * Math.sin(x / 3.0 * Math.PI)) * 2.0 / 3.0;
            ret += (150.0 * Math.sin(x / 12.0 * Math.PI) + 300.0 * Math.sin(x / 30.0 * Math.PI)) * 2.0 / 3.0;
            return ret;
        },

        // Tencent reverse geocoding expects GCJ02 coordinates within mainland China.
        convertWgs84ToGcj02(lat, lng) {
            if (this.isOutOfChina(lat, lng)) {
                return { lat, lng };
            }

            const a = 6378245.0;
            const ee = 0.00669342162296594323;
            let dLat = this.transformLat(lng - 105.0, lat - 35.0);
            let dLng = this.transformLng(lng - 105.0, lat - 35.0);
            const radLat = lat / 180.0 * Math.PI;
            let magic = Math.sin(radLat);

            magic = 1 - ee * magic * magic;

            const sqrtMagic = Math.sqrt(magic);

            dLat = (dLat * 180.0) / ((a * (1 - ee)) / (magic * sqrtMagic) * Math.PI);
            dLng = (dLng * 180.0) / (a / sqrtMagic * Math.cos(radLat) * Math.PI);

            return {
                lat: lat + dLat,
                lng: lng + dLng
            };
        },

        resolveLocationErrorMessage(error) {
            if (error && typeof error.code !== 'undefined') {
                switch (error.code) {
                    case 1:
                        return '定位权限被拒绝，请允许浏览器获取当前位置';
                    case 2:
                        return '无法获取当前位置，请检查设备定位是否开启';
                    case 3:
                        return '定位超时，请稍后重试';
                    default:
                        break;
                }
            }

            if (error && error.message) {
                return error.message;
            }

            return '获取地址失败，请稍后重试';
        },

        normalizeLocationFragment(value) {
            if (typeof value !== 'string') {
                return '';
            }

            return value
                .replace(/\s+/gu, ' ')
                .trim()
                .replace(/^[,，、;；|｜/／\\\-_.·•・\s]+|[,，、;；|｜/／\\\-_.·•・\s]+$/gu, '');
        },

        formatLocationCity(city) {
            const normalized = this.normalizeLocationFragment(city);

            if (!normalized) {
                return '';
            }

            return normalized.replace(/(?:特别行政区|市)$/u, '') || normalized;
        },

        stripLocationPlacePrefix(place) {
            let nextPlace = this.normalizeLocationFragment(place);

            if (!nextPlace) {
                return '';
            }

            const patterns = [
                /^[\u4E00-\u9FFF]{1,12}(?:区|县|旗|市)(?=[\u4E00-\u9FFFA-Za-z0-9])/u,
                /^[\u4E00-\u9FFFA-Za-z0-9]{1,24}(?:镇|乡|街道|街|路|大道|巷|胡同|村|社区|工业园|开发区)(?=[\u4E00-\u9FFFA-Za-z0-9])/u,
                /^[A-Za-z0-9一二三四五六七八九十百千零〇-]{1,12}(?:号|弄|栋|座|层|室)(?=[\u4E00-\u9FFFA-Za-z0-9])/u
            ];

            for (let index = 0; index < 5; index += 1) {
                let updated = false;

                for (const pattern of patterns) {
                    const match = nextPlace.match(pattern);

                    if (!match || !match[0]) {
                        continue;
                    }

                    const candidate = this.normalizeLocationFragment(nextPlace.slice(match[0].length));

                    if (!candidate) {
                        continue;
                    }

                    nextPlace = candidate;
                    updated = true;
                    break;
                }

                if (!updated) {
                    break;
                }
            }

            return nextPlace;
        },

        isGenericLocationPlace(place) {
            const normalized = this.normalizeLocationFragment(place);

            if (!normalized) {
                return true;
            }

            return /^(?:[\u4E00-\u9FFF]{1,12}(?:区|县|旗|市|镇|乡|街道|街|路|大道|巷|胡同|村|社区|工业园|开发区)|[A-Za-z0-9一二三四五六七八九十百千零〇-]{1,12}(?:号|弄|栋|座|层|室))$/u.test(normalized);
        },

        joinLocationParts(city, place) {
            const displayCity = this.formatLocationCity(city);
            const displayPlace = this.normalizeLocationFragment(place);

            if (displayCity && displayPlace && displayCity !== displayPlace) {
                return `${displayCity}·${displayPlace}`;
            }

            return displayPlace || displayCity;
        },

        extractTencentPoiTitle(result) {
            const candidates = [
                result?.pois?.[0]?.title,
                result?.pois?.[0]?.name,
                result?.address_reference?.landmark_l2?.title,
                result?.address_reference?.landmark_l1?.title,
                result?.address_reference?.famous_area?.title,
                result?.address_reference?.business_area?.title,
                result?.address_reference?.street_number?.title
            ];

            const city = this.formatLocationCity(result?.address_component?.city || result?.ad_info?.city || '');
            const district = this.normalizeLocationFragment(result?.address_component?.district || '');

            for (const candidate of candidates) {
                const title = this.normalizeLocationFragment(candidate);

                if (!title || title === city || title === district || this.isGenericLocationPlace(title)) {
                    continue;
                }

                return title;
            }

            return '';
        },

        simplifyTencentAddressText(result, city) {
            let place = this.normalizeLocationFragment(
                result?.formatted_addresses?.recommend ||
                result?.address ||
                ''
            );

            if (!place) {
                return '';
            }

            const fragments = [
                result?.address_component?.nation,
                result?.address_component?.province,
                result?.address_component?.city,
                result?.address_component?.district,
                result?.address_component?.street,
                result?.address_component?.street_number
            ]
                .map((fragment) => this.normalizeLocationFragment(fragment))
                .filter(Boolean);

            fragments.forEach((fragment) => {
                if (place.startsWith(fragment)) {
                    place = this.normalizeLocationFragment(place.slice(fragment.length));
                }
            });

            place = this.stripLocationPlacePrefix(place);

            if (!place || place === city || this.isGenericLocationPlace(place)) {
                return '';
            }

            return place;
        },

        waitForTencentMapService() {
            if (
                window.TMap &&
                window.TMap.LatLng &&
                window.TMap.service &&
                typeof window.TMap.service.Geocoder === 'function'
            ) {
                return Promise.resolve(window.TMap);
            }

            return new Promise((resolve, reject) => {
                const startedAt = Date.now();
                const timeoutMs = 10000;

                const check = () => {
                    if (
                        window.TMap &&
                        window.TMap.LatLng &&
                        window.TMap.service &&
                        typeof window.TMap.service.Geocoder === 'function'
                    ) {
                        resolve(window.TMap);
                        return;
                    }

                    if (Date.now() - startedAt >= timeoutMs) {
                        reject(new Error('腾讯地图服务类库加载失败，请刷新页面后重试'));
                        return;
                    }

                    setTimeout(check, 120);
                };

                check();
            });
        },

        async getTencentGeocoder() {
            if (this.tencentGeocoder) {
                return this.tencentGeocoder;
            }

            await this.waitForTencentMapService();
            this.tencentGeocoder = new window.TMap.service.Geocoder();

            return this.tencentGeocoder;
        },

        extractTencentAddress(payload) {
            if (!payload || !payload.result) {
                return '';
            }

            const result = payload.result;
            const city = result.address_component?.city || result.ad_info?.city || '';
            const poiTitle = this.extractTencentPoiTitle(result);

            if (poiTitle) {
                return this.joinLocationParts(city, poiTitle);
            }

            return this.joinLocationParts(city, this.simplifyTencentAddressText(result, this.formatLocationCity(city)));
        },

        async requestCurrentLocation() {
            if (this.locationLoading) {
                return;
            }

            if (!window.TIMELLOW_CONFIG || !window.TIMELLOW_CONFIG.locationLookupEnabled) {
                alert('请先在主题设置中填写腾讯地图 API Key');
                return;
            }

            if (!navigator.geolocation) {
                alert('当前浏览器不支持定位');
                return;
            }

            this.locationLoading = true;

            try {
                await this.waitForTencentMapService();

                const location = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(
                        resolve,
                        reject,
                        {
                            enableHighAccuracy: false,
                            timeout: 20000,
                            maximumAge: 300000
                        }
                    );
                });

                const converted = this.convertWgs84ToGcj02(
                    location.coords.latitude,
                    location.coords.longitude
                );
                const geocoder = await this.getTencentGeocoder();
                const result = await geocoder.getAddress({
                    location: new window.TMap.LatLng(converted.lat, converted.lng),
                    getPoi: true,
                    poiOptions: 'address_format=short;policy=1'
                });

                const address = this.extractTencentAddress(result);

                if (!address) {
                    throw new Error(result && result.message ? result.message : '获取地址失败，请稍后重试');
                }

                this.position = address;
            } catch (error) {
                alert(this.resolveLocationErrorMessage(error));
            } finally {
                this.locationLoading = false;
            }
        },

        async submitPost() {
            if (this.submitting) {
                return;
            }

            if (!this.postContent.trim() && this.mediaFiles.length === 0) {
                alert('请输入内容或选择图片/视频');
                return;
            }

            this.submitStatus = this.isEditing ? '保存中...' : '发布中...';
            this.submitting = true;

            try {
                const formData = new FormData();
                formData.append('content', this.postContent);
                formData.append('position', this.position);
                formData.append('visibility', this.visibility);
                formData.append('isAdvertise', this.isAdvertise ? '1' : '0');
                formData.append('isSticky', this.visibility === 'public' && this.isSticky ? '1' : '0');
                formData.append('attachment_ids', JSON.stringify(this.mediaFiles.map((media) => media.id).filter(Boolean)));
                formData.append('returnUrl', window.location.href);

                if (this.isEditing) {
                    formData.append('postId', String(this.editingPostId));
                }

                const action = this.isEditing ? 'updatePost' : 'createPost';
                const response = await fetch(`${window.TIMELLOW_CONFIG.actionUrl}?do=${action}`, {
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': window.TIMELLOW_CONFIG.restNonce || ''
                    },
                    credentials: 'same-origin',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    this.submitStatus = result.message || (this.isEditing ? '保存成功' : '发布成功');

                    setTimeout(() => {
                        window.location.href = result.redirect || window.location.href;
                    }, 800);
                    return;
                }

                this.submitStatus = '';
                this.submitting = false;
                alert(result.message || '发布失败，请稍后重试');
            } catch (error) {
                this.submitStatus = '';
                this.submitting = false;
                alert('网络错误，请稍后重试');
            }
        }
    };
}
</script>

<div class="write-modal" x-cloak
     x-data="timellowWriteModalManager()"
     x-show="writeModalShow"
     x-transition.opacity.duration.300ms
     @click.self="close()"
     @keydown.escape.window="if (writeModalShow) close()"
     @write-modal-open.window="open($event.detail || null)">
    <div class="write-container" x-transition.scale.duration.300ms>
        <div class="write-modal-header">
            <div class="write-modal-title" x-text="modalTitle"></div>
            <div class="write-modal-actions">
                <?php if (current_user_can('edit_posts')) : ?>
                    <button type="button" class="write-publish-btn" :disabled="submitting" @click="submitPost()">
                        <span x-text="submitButtonText"></span>
                    </button>
                <?php endif; ?>
                <button type="button" class="write-modal-close" @click="close()">×</button>
            </div>
        </div>

        <?php if (!is_user_logged_in()) : ?>
            <div class="write-modal-body">
                <div class="edit-login-required">
                    <div class="login-required-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="48" height="48">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </div>
                    <h3>请先登录</h3>
                    <p>登录后即可在前端撰写并发布内容</p>
                    <button type="button" class="login-required-btn"
                            @click="close(); $nextTick(() => { document.querySelector('.login-modal')._x_dataStack[0].loginModalShow = true })">
                        立即登录
                    </button>
                </div>
            </div>
        <?php elseif (!current_user_can('edit_posts')) : ?>
            <div class="write-modal-body">
                <div class="edit-login-required">
                    <div class="login-required-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="48" height="48">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                        </svg>
                    </div>
                    <h3>暂无发布权限</h3>
                    <p>当前账号无法在前端发布内容</p>
                </div>
            </div>
        <?php else : ?>
            <div class="write-modal-body">
                <form @submit.prevent="submitPost()">
                    <div class="edit-content-area">
                        <textarea x-ref="postContent"
                                  name="content"
                                  placeholder="这一刻的想法..."
                                  x-model="postContent"
                                  @input="autoResize($event)"
                                  rows="4"></textarea>
                    </div>

                    <div class="edit-media-section">
                        <div class="edit-media-preview" :class="'media-count-' + mediaFiles.length" x-show="mediaFiles.length > 0">
                            <template x-for="(file, index) in mediaFiles" :key="index">
                                <div class="media-preview-item" :class="{'is-video': file.mediaType === 'video'}">
                                    <template x-if="file.mediaType === 'image'">
                                        <img :src="file.preview" alt="预览图片">
                                    </template>
                                    <template x-if="file.mediaType === 'video'">
                                        <video :src="file.preview" muted></video>
                                    </template>
                                    <button type="button" class="media-remove-btn" @click="removeMedia(index)">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <div class="video-indicator" x-show="file.mediaType === 'video'">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" width="20" height="20">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </div>
                                </div>
                            </template>

                            <div class="media-add-btn" @click="openMediaLibrary()" x-show="mediaFiles.length > 0 && mediaFiles.length < 9 && !hasVideoMedia">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="28" height="28">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </div>
                        </div>

                        <div class="media-empty-add" @click="openMediaLibrary()" x-show="mediaFiles.length === 0">
                            <div class="media-empty-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="32" height="32">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                            </div>
                            <span class="media-empty-text">图片/视频</span>
                        </div>
                    </div>

                    <div class="edit-options">
                        <div class="edit-option-item" @click="requestCurrentLocation()">
                            <div class="option-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                </svg>
                            </div>
                            <div class="option-content">
                                <span class="option-label">所在位置</span>
                            </div>
                            <div class="option-value" x-show="locationLoading || position">
                                <span x-text="locationLoading ? '定位中...' : position"></span>
                            </div>
                            <div class="option-arrow">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </div>
                        </div>

                        <div class="edit-option-item" @click="showVisibilityPicker = !showVisibilityPicker">
                            <div class="option-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                </svg>
                            </div>
                            <div class="option-content">
                                <span class="option-label">谁可以看</span>
                            </div>
                            <div class="option-value">
                                <span x-text="visibilityText"></span>
                            </div>
                            <div class="option-arrow">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </div>
                        </div>

                        <div class="edit-visibility-picker" x-show="showVisibilityPicker" x-transition>
                            <div class="visibility-option"
                                 :class="{'active': visibility === 'public'}"
                                 @click="visibility = 'public'; showVisibilityPicker = false">
                                <div class="visibility-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20" height="20">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12.75 3.03v.568c0 .334.148.65.405.864l1.068.89c.442.369.535 1.01.216 1.49l-.51.766a2.25 2.25 0 0 1-1.161.886l-.143.048a1.107 1.107 0 0 0-.57 1.664c.369.555.169 1.307-.427 1.605L9 13.125l.423 1.059a.956.956 0 0 1-1.652.928l-.679-.906a1.125 1.125 0 0 0-1.906.172L4.5 15.75l-.612.153M12.75 3.031a9 9 0 1 0 6.712 14.374M12.75 3.031a9 9 0 0 1 6.712 14.374m0 0-.177-.529A2.25 2.25 0 0 0 17.128 15H16.5l-.324-.324a1.453 1.453 0 0 0-2.328.377l-.036.073a1.586 1.586 0 0 1-.982.816l-.99.282c-.55.157-.894.702-.8 1.267l.073.438c.08.474.49.821.97.821.846 0 1.598.542 1.865 1.345l.215.643m5.276-3.67a9.012 9.012 0 0 1-5.276 3.67m0 0a9 9 0 0 1-10.275-4.835M15.75 9c0 .896-.393 1.7-1.016 2.25" />
                                    </svg>
                                </div>
                                <div class="visibility-text">
                                    <span class="visibility-label">公开</span>
                                    <span class="visibility-desc">所有人可见</span>
                                </div>
                                <div class="visibility-check" x-show="visibility === 'public'">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                </div>
                            </div>

                            <div class="visibility-option"
                                 :class="{'active': visibility === 'private'}"
                                 @click="visibility = 'private'; isSticky = false; showVisibilityPicker = false">
                                <div class="visibility-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20" height="20">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                </div>
                                <div class="visibility-text">
                                    <span class="visibility-label">私密</span>
                                    <span class="visibility-desc">仅自己可见</span>
                                </div>
                                <div class="visibility-check" x-show="visibility === 'private'">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <?php if (timellow_user_can_sticky_posts('post')) : ?>
                            <div class="edit-option-item" x-show="visibility === 'public'">
                                <div class="option-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 6.75h13.5m-12 3.75h10.5m-9 3.75h7.5m-9.375 5.25 2.651-2.651a1.125 1.125 0 0 1 .796-.329h4.607c.298 0 .584.118.795.33L18.375 19.5" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v13.5" />
                                    </svg>
                                </div>
                                <div class="option-content">
                                    <span class="option-label">文章置顶</span>
                                </div>
                                <div class="option-switch">
                                    <label class="switch">
                                        <input type="checkbox" x-model="isSticky">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="edit-option-item">
                            <div class="option-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" />
                                </svg>
                            </div>
                            <div class="option-content">
                                <span class="option-label">广告内容</span>
                            </div>
                            <div class="option-switch">
                                <label class="switch">
                                    <input type="checkbox" x-model="isAdvertise">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="edit-status" x-show="submitStatus" x-transition>
                        <span x-text="submitStatus"></span>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
