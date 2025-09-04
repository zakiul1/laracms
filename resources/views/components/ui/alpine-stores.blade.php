{{-- registers global Alpine stores + helpers --}}
<script>
    document.addEventListener('alpine:init', () => {
        // --- Toast store ---
        Alpine.store('toast', {
            list: [],
            show(message, type = 'success', timeout = 4000) {
                const id = Date.now() + Math.random();
                this.list.push({
                    id,
                    message,
                    type
                });
                if (timeout) setTimeout(() => this.dismiss(id), timeout);
            },
            dismiss(id) {
                this.list = this.list.filter(t => t.id !== id);
            }
        });

        // --- Confirm store ---
        // Usage:
        //   Alpine.store('confirm').ask('Delete?', () => doSomething())
        // Or via event:
        //   window.dispatchEvent(new CustomEvent('confirm', { detail: { message: 'Delete?', submit: 'formId' }}))
        Alpine.store('confirm', {
            open: false,
            message: '',
            onConfirm: null,
            ask(message, cb) {
                this.message = message;
                this.onConfirm = cb;
                this.open = true;
            },
            cancel() {
                this.open = false;
                this.message = '';
                this.onConfirm = null;
            },
            proceed() {
                try {
                    if (typeof this.onConfirm === 'function') this.onConfirm();
                } finally {
                    this.cancel();
                }
            }
        });

        // Listen for "confirm" events (no direct Alpine needed at call site)
        window.addEventListener('confirm', (e) => {
            const d = e.detail || {};
            const msg = d.message || 'Are you sure?';
            const submitId = d.submit; // optional: form id to submit
            const href = d.href; // optional: navigate to href
            const cb = () => {
                if (submitId) {
                    document.getElementById(submitId)?.submit();
                    return;
                }
                if (href) {
                    window.location.href = href;
                    return;
                }
            };
            Alpine.store('confirm').ask(msg, cb);
        });

        // Convenience helpers for anywhere
        window.showToast = (message, type = 'success', timeout = 4000) =>
            Alpine.store('toast').show(message, type, timeout);
    });
</script>
