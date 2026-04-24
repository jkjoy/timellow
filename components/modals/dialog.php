<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<script>
function timellowDialogManager() {
    return {
        dialogShow: false,
        mode: 'alert',
        title: '',
        message: '',
        confirmText: '知道了',
        cancelText: '取消',
        tone: 'default',
        pendingQueue: [],
        currentResolver: null,
        lastActiveElement: null,

        focusElement(element) {
            if (!element || typeof element.focus !== 'function') {
                return;
            }

            try {
                element.focus({ preventScroll: true });
            } catch (error) {
                element.focus();
            }
        },

        init() {
            const queuedItems = window.TimellowDialog && Array.isArray(window.TimellowDialog.__queue)
                ? window.TimellowDialog.__queue.slice()
                : [];

            window.TimellowDialog = {
                notice: (message, options = {}) => this.enqueue('alert', message, options),
                confirm: (message, options = {}) => this.enqueue('confirm', message, options)
            };

            this.pendingQueue = queuedItems;
            this.flushQueue();
        },

        enqueue(type, message, options = {}) {
            return new Promise((resolve) => {
                this.pendingQueue.push({
                    type: type,
                    message: typeof message === 'string' ? message : String(message || ''),
                    options: options || {},
                    resolve: resolve
                });

                this.flushQueue();
            });
        },

        flushQueue() {
            if (this.dialogShow || this.pendingQueue.length === 0) {
                return;
            }

            this.openDialog(this.pendingQueue.shift());
        },

        openDialog(item) {
            const options = item.options || {};

            this.mode = item.type === 'confirm' ? 'confirm' : 'alert';
            this.title = options.title || (this.mode === 'confirm' ? '请确认操作' : '提示');
            this.message = item.message || '';
            this.confirmText = options.confirmText || (this.mode === 'confirm' ? '确认' : '知道了');
            this.cancelText = options.cancelText || '取消';
            this.tone = options.tone === 'danger' ? 'danger' : 'default';
            this.currentResolver = typeof item.resolve === 'function' ? item.resolve : null;
            this.lastActiveElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            this.dialogShow = true;

            if (document.body) {
                document.body.classList.add('timellow-dialog-open');
            }

            this.$nextTick(() => {
                const primaryButton = this.$refs.confirmButton || this.$refs.cancelButton || this.$refs.closeButton;

                this.focusElement(primaryButton);
            });
        },

        dismiss() {
            this.finish(this.mode === 'confirm' ? false : undefined);
        },

        confirmAction() {
            this.finish(this.mode === 'confirm' ? true : undefined);
        },

        finish(result) {
            const resolve = this.currentResolver;

            this.dialogShow = false;
            this.currentResolver = null;

            if (document.body) {
                document.body.classList.remove('timellow-dialog-open');
            }

            if (typeof resolve === 'function') {
                resolve(result);
            }

            if (this.lastActiveElement && typeof this.lastActiveElement.focus === 'function') {
                window.setTimeout(() => {
                    this.focusElement(this.lastActiveElement);
                }, 40);
            }

            window.setTimeout(() => {
                this.flushQueue();
            }, 60);
        }
    };
}
</script>

<div class="timellow-dialog-modal" x-cloak
     x-data="timellowDialogManager()"
     x-show="dialogShow"
     x-transition.opacity.duration.200ms
     @click.self="dismiss()"
     @keydown.escape.window="if (dialogShow) dismiss()">
    <div class="timellow-dialog-container"
         x-transition.scale.duration.200ms
         role="dialog"
         aria-modal="true"
         aria-labelledby="timellow-dialog-title"
         aria-describedby="timellow-dialog-message">
        <button type="button" class="timellow-dialog-close" @click="dismiss()" x-ref="closeButton" aria-label="关闭">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="timellow-dialog-header">
            <div class="timellow-dialog-icon" :class="{ 'is-danger': tone === 'danger' }">
                <template x-if="tone === 'danger'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008Zm8.25-3.758c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9Z" />
                    </svg>
                </template>
                <template x-if="tone !== 'danger'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25 12 11.25v5.25m0-8.25h.008v.008H12V8.25Zm8.25 3.75c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9Z" />
                    </svg>
                </template>
            </div>

            <div class="timellow-dialog-copy">
                <h3 class="timellow-dialog-title" id="timellow-dialog-title" x-text="title"></h3>
                <p class="timellow-dialog-message" id="timellow-dialog-message" x-text="message"></p>
            </div>
        </div>

        <div class="timellow-dialog-actions" :class="{ 'is-single': mode === 'alert' }">
            <template x-if="mode === 'confirm'">
                <button type="button" class="timellow-dialog-btn timellow-dialog-btn-secondary" @click="dismiss()" x-ref="cancelButton" x-text="cancelText"></button>
            </template>

            <button type="button"
                    class="timellow-dialog-btn"
                    :class="tone === 'danger' ? 'timellow-dialog-btn-danger' : 'timellow-dialog-btn-primary'"
                    @click="confirmAction()"
                    x-ref="confirmButton"
                    x-text="confirmText"></button>
        </div>
    </div>
</div>
