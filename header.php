<?php
if (!defined('ABSPATH')) {
    exit;
}

$timellow_comment_user = is_user_logged_in() ? wp_get_current_user() : null;
$timellow_comment_identity = null;

if ($timellow_comment_user instanceof WP_User) {
    $timellow_comment_identity = array(
        'author' => $timellow_comment_user->display_name !== '' ? $timellow_comment_user->display_name : $timellow_comment_user->user_login,
        'email' => $timellow_comment_user->user_email,
        'url' => $timellow_comment_user->user_url,
    );
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="renderer" content="webkit">
    <meta name="force-rendering" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <style>
        [x-cloak] { display: none !important; }
    </style>

    <script>
        (function() {
            const savedTheme = localStorage.getItem('timellow_theme_mode');
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else if (savedTheme === 'light') {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <script>
        window.TIMELLOW_CONFIG = {
            actionUrl: <?php echo wp_json_encode(timellow_get_relative_rest_url('timellow/v1/action')); ?>,
            homeUrl: <?php echo wp_json_encode(home_url('/')); ?>,
            defaultAvatar: <?php echo wp_json_encode(get_template_directory_uri() . '/assets/images/default-avatar.svg'); ?>,
            restNonce: <?php echo wp_json_encode(wp_create_nonce('wp_rest')); ?>,
            locationLookupEnabled: <?php echo wp_json_encode(timellow_get_frontend_tencent_map_key() !== ''); ?>,
            commentIdentityLocked: <?php echo wp_json_encode(is_user_logged_in()); ?>,
            currentCommentIdentity: <?php echo wp_json_encode($timellow_comment_identity); ?>,
            currentUserIsAdmin: <?php echo wp_json_encode(timellow_user_is_administrator()); ?>
        };

        const EMOJI_DATA = {
            '表情': ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🥸', '🤩', '🥳'],
            '手势': ['👋', '🤚', '🖐', '✋', '🖖', '👌', '🤌', '🤏', '✌', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝', '👍', '👎', '✊', '👊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏'],
            '动物': ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🐯', '🦁', '🐮', '🐷', '🐽', '🐸', '🐵', '🙈', '🙉', '🙊', '🐒', '🐔', '🐧', '🐦', '🐤', '🐣', '🐥', '🦆', '🦅', '🦉', '🦇', '🐺', '🐗'],
            '食物': ['🍎', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🍈', '🍒', '🍑', '🥭', '🍍', '🥥', '🥝', '🍅', '🍆', '🥑', '🥦', '🥬', '🥒', '🌶', '🌽', '🥕', '🧄', '🧅', '🥔', '🍠', '🥐', '🥯', '🍞', '🥖', '🥨'],
            '活动': ['⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎱', '🪀', '🏓', '🏸', '🏒', '🏑', '🥍', '🏏', '🪃', '🥅', '⛳', '🪁', '🏹', '🎣', '🤿', '🥊', '🥋', '🎽', '🛹', '🛼', '🛷', '⛸', '🥌'],
            '符号': ['❤', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮', '✝', '☪', '🕉', '☸', '✡', '🔯', '🕎', '☯', '☦', '🛐', '⛎', '♈']
        };

        if (!window.TimellowDialog) {
            const timellowDialogQueue = [];

            window.TimellowDialog = {
                __queue: timellowDialogQueue,
                notice(message, options = {}) {
                    return new Promise((resolve) => {
                        timellowDialogQueue.push({
                            type: 'alert',
                            message: typeof message === 'string' ? message : String(message || ''),
                            options: options || {},
                            resolve: resolve
                        });
                    });
                },
                confirm(message, options = {}) {
                    return new Promise((resolve) => {
                        timellowDialogQueue.push({
                            type: 'confirm',
                            message: typeof message === 'string' ? message : String(message || ''),
                            options: options || {},
                            resolve: resolve
                        });
                    });
                }
            };
        }

        function commentReplyManager() {
            return {
                activeCommentId: null,
                replyForm: null,

                init() {
                    document.addEventListener('click', this.handleClickOutside.bind(this));
                },

                showAlert(message, options = {}) {
                    return window.TimellowDialog.notice(message, options);
                },

                showConfirm(message, options = {}) {
                    return window.TimellowDialog.confirm(message, options);
                },

                togglePostTimeComment(event, postId) {
                    event.preventDefault();
                    event.stopPropagation();

                    const currentModal = document.getElementById(`ptcm-${postId}`);
                    if (!currentModal) return;

                    const currentAlpineComponent = Alpine.$data(currentModal);
                    if (!currentAlpineComponent) return;

                    if (currentAlpineComponent.ptcmShow) {
                        currentAlpineComponent.ptcmShow = false;
                        return;
                    }

                    this.hideAllPostTimeCommentModals();
                    currentAlpineComponent.ptcmShow = true;
                },

                hideAllPostTimeCommentModals() {
                    const allModals = document.querySelectorAll('.post-time-comment');
                    allModals.forEach(modal => {
                        const alpineComponent = Alpine.$data(modal);
                        if (alpineComponent && alpineComponent.ptcmShow !== undefined) {
                            alpineComponent.ptcmShow = false;
                        }
                    });
                },

                showReplyForm(event, postId, coid, authorName) {
                    event.preventDefault();

                    const clickedElement = event.target;
                    const commentItem = clickedElement.closest('.pcc-comment-item');
                    if (!commentItem) return;

                    if (!coid || coid === '0') {
                        const domCoid = commentItem.dataset.commentId;
                        if (domCoid && domCoid !== '0') {
                            coid = domCoid;
                        }
                    }

                    const formId = `reply-form-${postId}-${Date.now()}`;

                    if (this.activeCommentId === commentItem) {
                        this.removeReplyForm();
                        this.activeCommentId = null;
                        return;
                    }

                    this.removeReplyForm();

                    const form = this.createReplyForm(formId, postId, authorName, coid);
                    commentItem.parentNode.insertBefore(form, commentItem.nextSibling);
                    this.activeCommentId = commentItem;
                    this.hidePostTimeCommentModal(postId);
                    form.querySelector('input[name="reply_content"]').focus();
                },

                showPostReplyForm(event, postId) {
                    event.preventDefault();
                    event.stopPropagation();

                    this.removeReplyForm();

                    const formId = `post-reply-form-${postId}-${Date.now()}`;
                    const postItem = event.target.closest('.post-item');
                    if (!postItem) return;

                    const targetContainer = postItem.querySelector('.post-comment-container');
                    if (!targetContainer) return;

                    let commentList = targetContainer.querySelector('.pcc-comment-list');
                    if (!commentList) {
                        commentList = document.createElement('div');
                        commentList.className = 'pcc-comment-list';
                        targetContainer.appendChild(commentList);
                    }

                    targetContainer.style.display = '';

                    const form = this.createPostReplyForm(formId, postId);
                    targetContainer.appendChild(form);
                    this.activeCommentId = `post-${postId}`;
                    this.hidePostTimeCommentModal(postId);

                    setTimeout(() => {
                        const input = form.querySelector('input[name="reply_content"]') || form.querySelector('input[type="text"]');
                        if (input) {
                            input.focus();
                        }
                    }, 100);
                },

                getPostItem(postId) {
                    const likeList = document.querySelector(`.pcc-like-list[data-cid="${postId}"]`);

                    if (likeList) {
                        return likeList.closest('.post-item');
                    }

                    const commentContainer = document.querySelector(`.post-comment-container[data-cid="${postId}"]`);
                    return commentContainer ? commentContainer.closest('.post-item') : null;
                },

                getCommentDeleteButtonHtml(postId, commentId) {
                    if (!window.TIMELLOW_CONFIG || !window.TIMELLOW_CONFIG.currentUserIsAdmin) {
                        return '';
                    }

                    return `<button type="button" class="pcc-comment-delete" @click.stop="deleteComment($event, '${postId}', '${commentId}')">删除</button>`;
                },

                removeDeletedComments(postId, commentIds) {
                    const normalizedIds = Array.isArray(commentIds) ? commentIds : [commentIds];

                    normalizedIds
                        .map((id) => parseInt(id, 10))
                        .filter(Boolean)
                        .forEach((id) => {
                            document
                                .querySelectorAll(`.pcc-comment-item[data-comment-id="${id}"]`)
                                .forEach((item) => item.remove());
                        });

                    const commentContainer = document.querySelector(`.post-comment-container[data-cid="${postId}"]`);

                    if (!commentContainer) {
                        return;
                    }

                    const likeList = commentContainer.querySelector('.pcc-like-list');
                    const commentList = commentContainer.querySelector('.pcc-comment-list');
                    const hasLikes = likeList && window.getComputedStyle(likeList).display !== 'none';
                    const hasComments = commentList && commentList.querySelectorAll('.pcc-comment-item').length > 0;

                    if (!hasLikes && !hasComments) {
                        commentContainer.style.display = 'none';
                    }
                },

                async editPost(event, postId) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.hidePostTimeCommentModal(postId);

                    try {
                        const response = await fetch(`${window.TIMELLOW_CONFIG.actionUrl}?do=getPostEditorData&postId=${encodeURIComponent(postId)}`, {
                            method: 'GET',
                            credentials: 'same-origin',
                            headers: {
                                'X-WP-Nonce': window.TIMELLOW_CONFIG.restNonce || ''
                            }
                        });
                        const result = await response.json();

                        if (!result.success || !result.post) {
                            await this.showAlert(result.message || '说说编辑数据加载失败', {
                                title: '加载失败',
                                tone: 'danger'
                            });
                            return;
                        }

                        window.dispatchEvent(new CustomEvent('write-modal-open', {
                            detail: {
                                mode: 'edit',
                                post: result.post
                            }
                        }));
                    } catch (error) {
                        console.error('说说编辑数据加载失败:', error);
                        await this.showAlert('说说编辑数据加载失败，请稍后重试', {
                            title: '加载失败',
                            tone: 'danger'
                        });
                    }
                },

                async deletePost(event, postId, postTypeLabel = '说说') {
                    event.preventDefault();
                    event.stopPropagation();

                    const subjectText = postTypeLabel === '文章' ? '这篇文章' : '这条说说';
                    const confirmed = await this.showConfirm(`确定将${subjectText}移入回收站吗？`, {
                        title: `删除${postTypeLabel}`,
                        confirmText: '删除',
                        cancelText: '取消',
                        tone: 'danger'
                    });

                    if (!confirmed) {
                        return;
                    }

                    this.hidePostTimeCommentModal(postId);

                    try {
                        const response = await fetch(`${window.TIMELLOW_CONFIG.actionUrl}?do=deletePost`, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': window.TIMELLOW_CONFIG.restNonce || ''
                            },
                            body: JSON.stringify({
                                postId: postId
                            })
                        });
                        const result = await response.json();

                        if (!result.success) {
                            await this.showAlert(result.message || `${postTypeLabel}移入回收站失败，请稍后重试`, {
                                title: '删除失败',
                                tone: 'danger'
                            });
                            return;
                        }

                        this.removeReplyForm();

                        const postItem = event.target.closest('.post-item') || this.getPostItem(postId);

                        if (postItem && !postItem.classList.contains('post-detail-item')) {
                            postItem.remove();
                            return;
                        }

                        window.location.href = result.redirect || window.TIMELLOW_CONFIG.homeUrl || '/';
                    } catch (error) {
                        console.error(`${postTypeLabel}移入回收站失败:`, error);
                        await this.showAlert(`${postTypeLabel}移入回收站失败，请稍后重试`, {
                            title: '删除失败',
                            tone: 'danger'
                        });
                    }
                },

                async deleteComment(event, postId, commentId) {
                    event.preventDefault();
                    event.stopPropagation();

                    const confirmed = await this.showConfirm('确定删除这条评论吗？此操作不可恢复。', {
                        title: '删除评论',
                        confirmText: '删除',
                        cancelText: '取消',
                        tone: 'danger'
                    });

                    if (!confirmed) {
                        return;
                    }

                    try {
                        const response = await fetch(`${window.TIMELLOW_CONFIG.actionUrl}?do=deleteComment`, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': window.TIMELLOW_CONFIG.restNonce || ''
                            },
                            body: JSON.stringify({
                                commentId: commentId
                            })
                        });
                        const result = await response.json();

                        if (!result.success) {
                            await this.showAlert(result.message || '评论删除失败，请稍后重试', {
                                title: '删除失败',
                                tone: 'danger'
                            });
                            return;
                        }

                        this.removeReplyForm();
                        this.removeDeletedComments(postId, result.deletedIds || [commentId]);
                    } catch (error) {
                        console.error('评论删除失败:', error);
                        await this.showAlert('评论删除失败，请稍后重试', {
                            title: '删除失败',
                            tone: 'danger'
                        });
                    }
                },

                getCommentIdentityState() {
                    const config = window.TIMELLOW_CONFIG || {};
                    const isLocked = !!config.commentIdentityLocked;
                    const lockedIdentity = config.currentCommentIdentity || {};
                    const author = isLocked ? (lockedIdentity.author || '') : (localStorage.getItem('timellow_comment_author') || '');
                    const email = isLocked ? (lockedIdentity.email || '') : (localStorage.getItem('timellow_comment_email') || '');
                    const url = isLocked ? (lockedIdentity.url || '') : (localStorage.getItem('timellow_comment_url') || '');

                    return {
                        isLocked: isLocked,
                        author: author,
                        email: email,
                        url: url,
                        hasStoredIdentity: !isLocked && author !== '' && email !== ''
                    };
                },

                getCommentIdentityFields(identity) {
                    if (identity.isLocked) {
                        return '';
                    }

                    if (identity.hasStoredIdentity) {
                        return `
                            <div class="reply-form-identity-summary" x-show="!replyIdentityExpanded">
                                <span class="reply-form-identity-name" x-text="replyAuthor"></span>
                                <button type="button" class="reply-form-edit-btn" @click="replyIdentityExpanded = true">修改</button>
                            </div>
                            <div class="reply-form-user-info" x-show="replyIdentityExpanded" style="display: none;">
                                <div class="reply-form-input">
                                    <input type="text" name="author_name" placeholder="昵称" x-bind:required="replyIdentityExpanded" x-bind:disabled="!replyIdentityExpanded" x-model="replyAuthor">
                                </div>
                                <div class="reply-form-input">
                                    <input type="email" name="author_email" placeholder="邮箱" x-bind:required="replyIdentityExpanded" x-bind:disabled="!replyIdentityExpanded" x-model="replyEmail">
                                </div>
                                <div class="reply-form-input">
                                    <input type="url" name="author_url" placeholder="网址" x-bind:disabled="!replyIdentityExpanded" x-model="replyUrl">
                                </div>
                            </div>
                        `;
                    }

                    return `
                        <div class="reply-form-user-info">
                            <div class="reply-form-input">
                                <input type="text" name="author_name" placeholder="昵称" required x-model="replyAuthor">
                            </div>
                            <div class="reply-form-input">
                                <input type="email" name="author_email" placeholder="邮箱" required x-model="replyEmail">
                            </div>
                            <div class="reply-form-input">
                                <input type="url" name="author_url" placeholder="网址" x-model="replyUrl">
                            </div>
                        </div>
                    `;
                },

                resolveCommentIdentity(form) {
                    const identity = this.getCommentIdentityState();
                    const authorInput = form.querySelector('input[name="author_name"]');
                    const emailInput = form.querySelector('input[name="author_email"]');
                    const urlInput = form.querySelector('input[name="author_url"]');

                    return {
                        isLocked: identity.isLocked,
                        author: authorInput ? authorInput.value.trim() : identity.author,
                        email: emailInput ? emailInput.value.trim() : identity.email,
                        url: urlInput ? urlInput.value.trim() : identity.url
                    };
                },

                persistGuestCommentIdentity(identity) {
                    if (identity.isLocked) {
                        return;
                    }

                    localStorage.setItem('timellow_comment_author', identity.author);
                    localStorage.setItem('timellow_comment_email', identity.email);
                    localStorage.setItem('timellow_comment_url', identity.url);
                },

                createPostReplyForm(formId, postId) {
                    const form = document.createElement('div');
                    form.className = 'reply-form-container post-reply-form';
                    form.id = formId;

                    const identity = this.getCommentIdentityState();

                    form.innerHTML = `
                        <div class="reply-form" x-data="{replyAuthor: '${this.escapeHtml(identity.author)}', replyEmail: '${this.escapeHtml(identity.email)}', replyUrl: '${this.escapeHtml(identity.url)}', replyIdentityExpanded: ${identity.hasStoredIdentity ? 'false' : 'true'}, replyContent: '', emojiPickerShow: false, currentEmojiTab: '表情'}">
                            <div class="reply-form-header">
                                <strong>发表评论</strong>
                                <button type="button" class="reply-form-close">×</button>
                            </div>
                            <form>
                                ${this.getCommentIdentityFields(identity)}
                                <div class="reply-form-input">
                                    <input type="text" name="reply_content" placeholder="写下你的评论..." required x-model="replyContent">
                                </div>
                                <div class="reply-form-bottom">
                                    <div class="reply-form-emoji-container">
                                        <button type="button" class="reply-form-emoji-toggle" @click.stop="emojiPickerShow = !emojiPickerShow">
                                            😀 <span>表情</span>
                                        </button>
                                        <div class="reply-form-emoji-picker" :class="{'show': emojiPickerShow}" @click.stop>
                                            <div class="emoji-picker-header">
                                                <span class="emoji-picker-title">选择表情</span>
                                                <button type="button" class="emoji-picker-close" @click="emojiPickerShow = false">×</button>
                                            </div>
                                            <div class="emoji-picker-tabs">
                                                ${Object.keys(EMOJI_DATA).map(tab => `
                                                    <button type="button" class="emoji-tab" :class="{'active': currentEmojiTab === '${tab}'}" @click="currentEmojiTab = '${tab}'">${tab}</button>
                                                `).join('')}
                                            </div>
                                            <div class="emoji-picker-content">
                                                ${Object.entries(EMOJI_DATA).map(([category, emojis]) =>
                                                    emojis.map(emoji => `
                                                        <span class="emoji-item" x-show="currentEmojiTab === '${category}'" @click="replyContent += '${emoji}'; emojiPickerShow = false">${emoji}</span>
                                                    `).join('')
                                                ).join('')}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="reply-form-actions">
                                        <button type="submit" class="reply-submit-btn">发表评论</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    `;

                    const closeBtn = form.querySelector('.reply-form-close');
                    const submitForm = form.querySelector('form');

                    closeBtn.addEventListener('click', () => {
                        this.removeReplyForm();
                    });

                    submitForm.addEventListener('submit', (e) => {
                        e.preventDefault();
                        this.submitPostReply(e, postId);
                    });

                    return form;
                },

                async submitPostReply(event, postId) {
                    const form = event.target;
                    const identity = this.resolveCommentIdentity(form);
                    const content = form.querySelector('input[name="reply_content"]').value.trim();

                    if (!content || (!identity.isLocked && (!identity.author || !identity.email))) {
                        await this.showAlert('请填写必要信息', {
                            title: '信息不完整'
                        });
                        return;
                    }

                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalText = submitBtn.textContent;
                    submitBtn.disabled = true;
                    submitBtn.textContent = '提交中...';

                    try {
                        const response = await fetch(`${window.TIMELLOW_CONFIG.actionUrl}?do=addComment`, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': window.TIMELLOW_CONFIG.restNonce,
                            },
                            body: JSON.stringify({
                                author: identity.author,
                                mail: identity.email,
                                url: identity.url || '',
                                text: content,
                                cid: postId,
                                coid: 0
                            })
                        });

                        const result = await response.json();

                        if (result.success) {
                            this.persistGuestCommentIdentity(identity);
                            this.addCommentToList(postId, result.comment);
                            this.removeReplyForm();
                        } else {
                            await this.showAlert(result.message || '评论发表失败，请稍后重试', {
                                title: '评论发表失败',
                                tone: 'danger'
                            });
                        }
                    } catch (error) {
                        console.error('评论提交错误:', error);
                        await this.showAlert('网络错误，请稍后重试', {
                            title: '网络错误',
                            tone: 'danger'
                        });
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                },

                createReplyForm(formId, postId, authorName, coid) {
                    const form = document.createElement('div');
                    form.className = 'reply-form-container';
                    form.id = formId;
                    form.dataset.coid = coid;

                    const identity = this.getCommentIdentityState();

                    form.innerHTML = `
                        <div class="reply-form" x-data="{replyAuthor: '${this.escapeHtml(identity.author)}', replyEmail: '${this.escapeHtml(identity.email)}', replyUrl: '${this.escapeHtml(identity.url)}', replyIdentityExpanded: ${identity.hasStoredIdentity ? 'false' : 'true'}, replyContent: '', emojiPickerShow: false, currentEmojiTab: '表情'}">
                            <div class="reply-form-header">
                                <strong>回复 ${authorName}</strong>
                                <button type="button" class="reply-form-close">×</button>
                            </div>
                            <form>
                                ${this.getCommentIdentityFields(identity)}
                                <div class="reply-form-input">
                                    <input type="text" name="reply_content" placeholder="写下你的回复..." required x-model="replyContent">
                                </div>
                                <div class="reply-form-bottom">
                                    <div class="reply-form-emoji-container">
                                        <button type="button" class="reply-form-emoji-toggle" @click.stop="emojiPickerShow = !emojiPickerShow">
                                            😀 <span>表情</span>
                                        </button>
                                        <div class="reply-form-emoji-picker" :class="{'show': emojiPickerShow}" @click.stop>
                                            <div class="emoji-picker-header">
                                                <span class="emoji-picker-title">选择表情</span>
                                                <button type="button" class="emoji-picker-close" @click="emojiPickerShow = false">×</button>
                                            </div>
                                            <div class="emoji-picker-tabs">
                                                ${Object.keys(EMOJI_DATA).map(tab => `
                                                    <button type="button" class="emoji-tab" :class="{'active': currentEmojiTab === '${tab}'}" @click="currentEmojiTab = '${tab}'">${tab}</button>
                                                `).join('')}
                                            </div>
                                            <div class="emoji-picker-content">
                                                ${Object.entries(EMOJI_DATA).map(([category, emojis]) =>
                                                    emojis.map(emoji => `
                                                        <span class="emoji-item" x-show="currentEmojiTab === '${category}'" @click="replyContent += '${emoji}'; emojiPickerShow = false">${emoji}</span>
                                                    `).join('')
                                                ).join('')}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="reply-form-actions">
                                        <button type="submit" class="reply-submit-btn">发表回复</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    `;

                    const closeBtn = form.querySelector('.reply-form-close');
                    const submitForm = form.querySelector('form');

                    closeBtn.addEventListener('click', () => {
                        this.removeReplyForm();
                    });

                    submitForm.addEventListener('submit', (e) => {
                        e.preventDefault();
                        this.submitReply(e, postId);
                    });

                    return form;
                },

                async submitReply(event, postId) {
                    const form = event.target;
                    const identity = this.resolveCommentIdentity(form);
                    const content = form.querySelector('input[name="reply_content"]').value.trim();

                    if (!content || (!identity.isLocked && (!identity.author || !identity.email))) {
                        await this.showAlert('请填写必要信息', {
                            title: '信息不完整'
                        });
                        return;
                    }

                    const formContainer = form.closest('.reply-form-container');
                    const coid = formContainer ? (formContainer.dataset.coid || 0) : 0;
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalText = submitBtn.textContent;
                    submitBtn.disabled = true;
                    submitBtn.textContent = '提交中...';

                    try {
                        const response = await fetch(`${window.TIMELLOW_CONFIG.actionUrl}?do=addComment`, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': window.TIMELLOW_CONFIG.restNonce,
                            },
                            body: JSON.stringify({
                                author: identity.author,
                                mail: identity.email,
                                url: identity.url || '',
                                text: content,
                                cid: postId,
                                coid: coid
                            })
                        });

                        const result = await response.json();

                        if (result.success) {
                            this.persistGuestCommentIdentity(identity);
                            this.addCommentToList(postId, result.comment);
                            this.removeReplyForm();
                        } else {
                            await this.showAlert(result.message || '回复发表失败，请稍后重试', {
                                title: '回复发表失败',
                                tone: 'danger'
                            });
                        }
                    } catch (error) {
                        console.error('回复提交错误:', error);
                        await this.showAlert('网络错误，请稍后重试', {
                            title: '网络错误',
                            tone: 'danger'
                        });
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                },

                removeReplyForm() {
                    const existingForms = document.querySelectorAll('.reply-form-container');
                    existingForms.forEach(form => {
                        const commentContainer = form.closest('.post-comment-container');
                        form.remove();

                        if (commentContainer) {
                            const likeList = commentContainer.querySelector('.pcc-like-list');
                            const commentList = commentContainer.querySelector('.pcc-comment-list');
                            const hasLikes = likeList && likeList.style.display !== 'none';
                            const hasComments = commentList && commentList.querySelectorAll('.pcc-comment-item').length > 0;

                            if (!hasLikes && !hasComments) {
                                commentContainer.style.display = 'none';
                            }
                        }
                    });

                    this.activeCommentId = null;
                },

                addCommentToList(postId, commentData) {
                    const allPostItems = document.querySelectorAll('.post-item');
                    let targetCommentList = null;

                    for (const item of allPostItems) {
                        const likeList = item.querySelector('.pcc-like-list');
                        if (likeList && likeList.dataset.cid == postId) {
                            targetCommentList = item.querySelector('.pcc-comment-list');
                            break;
                        }
                    }

                    if (!targetCommentList) {
                        return;
                    }

                    const commentItem = document.createElement('div');
                    commentItem.className = 'pcc-comment-item';
                    commentItem.dataset.commentId = commentData.coid;

                    const isAdmin = commentData.userGroup === 'administrator';
                    const authorBadge = isAdmin ? '<span class="author-badge">作者</span>' : '';
                    const deleteButton = this.getCommentDeleteButtonHtml(postId, commentData.coid);

                    if (!commentData.parent || commentData.parent == 0) {
                        commentItem.innerHTML = `
                            <a href="${commentData.url || '#'}">${this.escapeHtml(commentData.author)}</a>
                            ${authorBadge}
                            <span>:</span>
                            <span class="cursor-help pcc-comment-content" @click="showReplyForm($event, '${postId}', '${commentData.coid}', '${this.escapeHtml(commentData.author)}')">${this.escapeHtml(commentData.text)}</span>
                            ${deleteButton}
                        `;
                    } else {
                        const parentComment = targetCommentList.querySelector(`[data-comment-id="${commentData.parent}"]`);
                        let parentAuthor = '原评论';
                        let parentUrl = '#';
                        let parentAuthorBadge = '';

                        if (parentComment) {
                            const parentLink = parentComment.querySelector('a');
                            if (parentLink) {
                                parentAuthor = parentLink.textContent;
                                parentUrl = parentLink.href;
                            }

                            const parentBadge = parentComment.querySelector('.author-badge');
                            if (parentBadge) {
                                parentAuthorBadge = '<span class="author-badge">作者</span>';
                            }
                        }

                        commentItem.innerHTML = `
                            <a href="${commentData.url || '#'}">${this.escapeHtml(commentData.author)}</a>
                            ${authorBadge}
                            <span>回复</span>
                            <a href="${parentUrl}">${this.escapeHtml(parentAuthor)}</a>
                            ${parentAuthorBadge}
                            <span>:</span>
                            <span class="cursor-help pcc-comment-content" @click="showReplyForm($event, '${postId}', '${commentData.coid}', '${this.escapeHtml(commentData.author)}')">${this.escapeHtml(commentData.text)}</span>
                            ${deleteButton}
                        `;
                    }

                    if (!commentData.parent || commentData.parent == 0) {
                        if (targetCommentList.firstChild) {
                            targetCommentList.insertBefore(commentItem, targetCommentList.firstChild);
                        } else {
                            targetCommentList.appendChild(commentItem);
                        }
                    } else {
                        const parentComment = targetCommentList.querySelector(`[data-comment-id="${commentData.parent}"]`);
                        if (parentComment && parentComment.nextSibling) {
                            targetCommentList.insertBefore(commentItem, parentComment.nextSibling);
                        } else {
                            targetCommentList.appendChild(commentItem);
                        }
                    }

                    if (window.Alpine) {
                        Alpine.initTree(commentItem);
                    }
                },

                escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                },

                hidePostTimeCommentModal(postId) {
                    const modal = document.getElementById(`ptcm-${postId}`);
                    if (modal) {
                        const alpineComponent = Alpine.$data(modal);
                        if (alpineComponent && alpineComponent.ptcmShow !== undefined) {
                            alpineComponent.ptcmShow = false;
                        }
                    }
                },

                handleClickOutside(event) {
                    if (this.activeCommentId && !event.target.closest('.reply-form-container') && !event.target.closest('.pcc-comment-content') && !event.target.closest('.ptcm-comment')) {
                        this.removeReplyForm();
                    }

                    if (!event.target.closest('.ptc-more') && !event.target.closest('.post-time-comment-modal')) {
                        this.hideAllPostTimeCommentModals();
                    }
                }
            }
        }
    </script>

    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
