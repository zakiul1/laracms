// resources/js/media.js
// WP-like Media Library (Alpine)
// - Pagination + per-page
// - Bulk actions (trash/restore/force)
// - Inline dropzone (no modal)
// - Preview & remove before upload
// - Category REQUIRED to enqueue/upload
// - Runtime category create
// - Per-file + overall progress, error handling
// - Grid, search, filters, inspector

function mediaLib() {
    const R =
        typeof window !== "undefined" && window.MediaRoutes
            ? window.MediaRoutes
            : {
                  list: "/admin/media/list",
                  upload: "/admin/media/upload",
                  meta: "/admin/media/meta/:id",
                  replace: "/admin/media/replace/:id",
                  destroy: "/admin/media/:id",
                  bulkDelete: "/admin/media/bulk-delete",
                  bulkRestore: "/admin/media/bulk-restore",
                  bulkForce: "/admin/media/bulk-force-delete",
                  catsJson: "/admin/media/categories/json",
                  quickCat: "/admin/media/categories/quick",
                  csrf:
                      typeof document !== "undefined"
                          ? document.querySelector('meta[name="csrf-token"]')
                                ?.content
                          : "",
              };
    const CSRF =
        R.csrf ||
        (typeof document !== "undefined"
            ? document.querySelector('meta[name="csrf-token"]')?.content
            : "");

    return {
        /* ---------------- GRID / FILTERS ---------------- */
        items: [],
        loading: false,
        filters: {
            q: "",
            term_taxonomy_id: "",
            images_only: true,
            sort: "newest",
        },

        // server-side pagination
        pagination: { page: 1, perPage: 40, lastPage: 1, total: 0 },

        /* ---------------- CATEGORIES ---------------- */
        categories: [],
        categoriesLoading: false,

        /* ---------------- BULK SELECTION ---------------- */
        selected: new Set(),

        /* ---------------- TOAST ---------------- */
        toast: { open: false, type: "info", msg: "" },
        showToast(msg, type = "info", ms = 3000) {
            this.toast.msg = msg;
            this.toast.type = type;
            this.toast.open = true;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => (this.toast.open = false), ms);
        },

        /* ---------------- INSPECTOR ---------------- */
        selectedId: null,
        inspector: { open: false, item: null },
        editor: { name: "", alt: "", caption: "", term_taxonomy_id: "" },

        /* ---------------- UPLOADER (INLINE) ---------------- */
        uploader: {
            categoryId: "", // REQUIRED before adding files
            creatingCat: false,
            newCatName: "",
            newCatParent: "",
            newCatErr: "",
            dragOver: false,
            queue: [], // [{file, name, size, type, url?, progress, status, error}]
            uploading: false,
            uploadedCount: 0,
        },

        /* ---------------- LIFECYCLE ---------------- */
        init() {
            this.fetchCategories();
            this.fetchPage(1);

            // react to filter changes
            this.$watch("filters.q", () => this.goto(1));
            this.$watch("filters.term_taxonomy_id", () => this.goto(1));
            this.$watch("filters.images_only", () => this.goto(1));
            this.$watch("filters.sort", () => this.goto(1));
        },

        /* ---------------- API HELPERS ---------------- */
        async fetchPage(page) {
            this.loading = true;
            this.pagination.page = page;

            const params = new URLSearchParams({
                page: this.pagination.page,
                per_page: this.pagination.perPage,
                q: this.filters.q || "",
                images_only: this.filters.images_only ? 1 : 0,
                sort: this.filters.sort || "newest",
            });
            if (this.filters.term_taxonomy_id)
                params.set("term_taxonomy_id", this.filters.term_taxonomy_id);

            try {
                const res = await fetch(`${R.list}?${params.toString()}`, {
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error(await res.text());
                const data = await res.json();

                this.items = data?.data || [];
                this.pagination.lastPage = data?.last_page || 1;
                this.pagination.total = data?.total || 0;

                // purge selections not on this page
                const idsOnPage = new Set(this.items.map((i) => i.id));
                this.selected.forEach((id) => {
                    if (!idsOnPage.has(id)) this.selected.delete(id);
                });
            } catch (e) {
                this.showToast("Failed to load media.", "error");
            } finally {
                this.loading = false;
            }
        },

        goto(n) {
            n = Math.max(1, Math.min(n, this.pagination.lastPage));
            return this.fetchPage(n);
        },

        /* ---------------- CATEGORIES ---------------- */
        fetchCategories() {
            this.categoriesLoading = true;
            fetch(R.catsJson, { headers: { Accept: "application/json" } })
                .then(async (r) => {
                    if (!r.ok) throw new Error(await r.text());
                    return r.json();
                })
                .then((data) => {
                    // accept either {items:[...]} or [...]
                    this.categories = Array.isArray(data)
                        ? data
                        : data.items || [];
                })
                .catch(() =>
                    this.showToast("Failed to load categories.", "error")
                )
                .finally(() => (this.categoriesLoading = false));
        },

        /* ---------------- UTIL ---------------- */
        human(bytes) {
            if (bytes == null) return "";
            const units = ["B", "KB", "MB", "GB"];
            let i = 0,
                v = Number(bytes);
            while (v >= 1024 && i < units.length - 1) {
                v /= 1024;
                i++;
            }
            return `${v.toFixed(i ? 1 : 0)} ${units[i]}`;
        },
        thumb(item) {
            return item.thumb_url || item.url || "";
        },
        copyUrl(url) {
            navigator.clipboard?.writeText(url || "");
            this.showToast("URL copied.", "success");
        },

        /* ---------------- BULK ---------------- */
        toggleSelect(id, checked) {
            checked ? this.selected.add(id) : this.selected.delete(id);
        },
        toggleSelectAll(e) {
            const check = e.target.checked;
            this.items.forEach((i) =>
                check ? this.selected.add(i.id) : this.selected.delete(i.id)
            );
        },
        async bulkPost(url) {
            const ids = Array.from(this.selected.values());
            if (!ids.length) return;
            const res = await fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": CSRF,
                    Accept: "application/json",
                },
                body: JSON.stringify({ ids }),
            });
            if (!res.ok) {
                this.showToast("Bulk action failed.", "error");
                return;
            }
            this.selected.clear();
            this.showToast("Done.", "success");
            this.fetchPage(this.pagination.page);
        },
        bulkTrash() {
            this.bulkPost(R.bulkDelete);
        },
        bulkRestore() {
            this.bulkPost(R.bulkRestore);
        },
        bulkForce() {
            if (confirm("Delete permanently? This cannot be undone."))
                this.bulkPost(R.bulkForce);
        },

        /* ---------------- INSPECTOR ---------------- */
        openInspector(item) {
            this.selectedId = item.id;
            this.inspector.item = item;
            this.editor.name = item.title || item.filename || "";
            this.editor.alt = item.alt || "";
            this.editor.caption = item.caption || "";
            this.editor.term_taxonomy_id = item.category?.id || "";
            this.inspector.open = true;
        },
        closeInspector() {
            this.inspector.open = false;
            this.selectedId = null;
            this.inspector.item = null;
        },
        saveMeta() {
            const id = this.inspector.item.id;
            const url = (R.meta || "").replace(":id", id);
            fetch(url, {
                method: "PATCH",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": CSRF,
                    Accept: "application/json",
                },
                body: JSON.stringify(this.editor),
            })
                .then(async (r) => {
                    if (!r.ok) throw new Error(await r.text());
                    return r.json();
                })
                .then(() => {
                    this.showToast("Saved.", "success");
                    this.fetchPage(this.pagination.page);
                })
                .catch(() => this.showToast("Save failed.", "error"));
        },
        replace(e) {
            const id = this.inspector.item.id;
            const url = (R.replace || "").replace(":id", id);
            const fd = new FormData();
            if (e.target.files?.[0]) fd.append("file", e.target.files[0]); // <-- controller expects 'file'
            fetch(url, {
                method: "POST",
                headers: { "X-CSRF-TOKEN": CSRF },
                body: fd,
            })
                .then(async (r) => {
                    if (!r.ok) throw new Error(await r.text());
                    return r.json();
                })
                .then(() => {
                    this.showToast("File replaced.", "success");
                    this.fetchPage(this.pagination.page);
                })
                .catch(() => this.showToast("Replace failed.", "error"))
                .finally(() => {
                    e.target.value = "";
                });
        },
        trash() {
            const id = this.inspector.item.id;
            const url = (R.destroy || "").replace(":id", id);
            fetch(url, {
                method: "DELETE",
                headers: { "X-CSRF-TOKEN": CSRF, Accept: "application/json" },
            })
                .then(async (r) => {
                    if (!r.ok) throw new Error(await r.text());
                    return r.json();
                })
                .then(() => {
                    this.closeInspector();
                    this.showToast("Moved to trash.", "success");
                    this.fetchPage(this.pagination.page);
                })
                .catch(() => this.showToast("Delete failed.", "error"));
        },

        /* ---------------- UPLOADER: CATEGORY ---------------- */
        createCategoryInline() {
            if (!this.uploader.newCatName.trim()) {
                this.uploader.newCatErr = "Name is required.";
                return;
            }
            this.uploader.creatingCat = true;
            this.uploader.newCatErr = "";
            fetch(R.quickCat, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": CSRF,
                    Accept: "application/json",
                },
                // Send both keys to be compatible with either controller style.
                body: JSON.stringify({
                    name: this.uploader.newCatName,
                    parent_tt_id: this.uploader.newCatParent || null,
                    parent_id: this.uploader.newCatParent || null,
                }),
            })
                .then(async (r) => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) {
                        const errs = data?.errors || {};
                        this.uploader.newCatErr =
                            (errs.name && errs.name[0]) ||
                            (errs.parent_tt_id && errs.parent_tt_id[0]) ||
                            (errs.parent_id && errs.parent_id[0]) ||
                            "Create failed.";
                        throw new Error("validation");
                    }
                    return data;
                })
                .then((data) => {
                    const cat = data?.category || data?.item;
                    if (cat?.id) {
                        this.categories.unshift({
                            id: cat.id,
                            name: cat.name ?? cat.term?.name ?? "—",
                            parent_id: cat.parent_id ?? null,
                        });
                        this.uploader.categoryId = String(cat.id);
                        this.showToast("Category created.", "success");
                        this.uploader.newCatName = "";
                        this.uploader.newCatParent = "";
                    }
                })
                .catch(() => {})
                .finally(() => (this.uploader.creatingCat = false));
        },

        /* ---------------- UPLOADER: QUEUE MGMT ---------------- */
        openFilePicker() {
            if (!this.uploader.categoryId) {
                this.showToast("Select a category to enable upload.", "error");
                return;
            }
            this.$refs.fileInput?.click();
        },
        onFileChange(e) {
            const files = e.target.files;
            this.addFiles(files);
            e.target.value = ""; // reset so same files trigger next time
        },
        onDragOver(e) {
            e.preventDefault();
            if (!this.uploader.categoryId) return;
            this.uploader.dragOver = true;
        },
        onDragLeave() {
            this.uploader.dragOver = false;
        },
        onDrop(e) {
            e.preventDefault();
            this.uploader.dragOver = false;
            this.addFiles(e.dataTransfer?.files || []);
        },
        addFiles(fileList) {
            if (!this.uploader.categoryId) {
                this.showToast("Please select a category first.", "error");
                return;
            }
            const MAX = 50 * 1024 * 1024; // 50MB
            const accept = (type) =>
                (type || "").startsWith("image/") ||
                type === "video/mp4" ||
                type === "video/webm" ||
                type === "application/pdf";

            Array.from(fileList || []).forEach((f) => {
                const item = {
                    file: f,
                    name: f.name,
                    size: f.size,
                    type: f.type || "",
                    url: null,
                    progress: 0,
                    status: "queued", // queued | uploading | done | error | skipped
                    error: "",
                };

                if (!accept(item.type)) {
                    item.status = "skipped";
                    item.error = "File type not allowed.";
                } else if (item.size > MAX) {
                    item.status = "skipped";
                    item.error = "File exceeds 50MB.";
                } else if (item.type.startsWith("image/")) {
                    item.url = URL.createObjectURL(f);
                }

                this.uploader.queue.push(item);
            });
        },
        removeFromQueue(idx) {
            const it = this.uploader.queue[idx];
            if (it?.url) URL.revokeObjectURL(it.url);
            this.uploader.queue.splice(idx, 1);
        },
        clearQueue() {
            this.uploader.queue.forEach(
                (it) => it.url && URL.revokeObjectURL(it.url)
            );
            this.uploader.queue = [];
            this.uploader.uploadedCount = 0;
        },

        get overallProgress() {
            if (!this.uploader.queue.length) return 0;
            const sum = this.uploader.queue.reduce(
                (a, b) => a + (b.progress || 0),
                0
            );
            return Math.round(sum / this.uploader.queue.length);
        },

        /* ---------------- UPLOADER: START/PROCESS ---------------- */
        startUpload() {
            if (!this.uploader.categoryId) {
                this.showToast("Select a category to upload.", "error");
                return;
            }
            const hasAny =
                this.uploader.queue.some((it) => it.status === "queued") ||
                this.uploader.queue.some((it) => it.status === "error");
            if (!hasAny) {
                this.showToast("No files to upload.", "info");
                return;
            }
            this.uploader.uploading = true;
            this.uploader.uploadedCount = 0;
            this._uploadNext();
        },

        _uploadNext() {
            const nextIdx = this.uploader.queue.findIndex(
                (it) => it.status === "queued"
            );
            if (nextIdx === -1) {
                // Finished
                this.uploader.uploading = false;
                const ok = this.uploader.queue.filter(
                    (i) => i.status === "done"
                ).length;
                const failed = this.uploader.queue.filter(
                    (i) => i.status === "error"
                ).length;
                this.showToast(
                    failed
                        ? `Uploaded ${ok}, ${failed} failed.`
                        : `Uploaded ${ok} file(s).`,
                    failed ? "error" : "success",
                    4000
                );
                // refresh grid & filter to selected category
                this.filters.term_taxonomy_id = this.uploader.categoryId;
                this.fetchPage(1);
                return;
            }

            const item = this.uploader.queue[nextIdx];
            item.status = "uploading";
            item.progress = 1;

            const fd = new FormData();
            fd.append("files[0]", item.file);
            fd.append("term_taxonomy_id", this.uploader.categoryId);

            const xhr = new XMLHttpRequest();
            xhr.open("POST", R.upload, true);
            xhr.setRequestHeader("X-CSRF-TOKEN", CSRF);
            xhr.upload.addEventListener("progress", (e) => {
                if (e.lengthComputable) {
                    item.progress = Math.max(
                        1,
                        Math.round((e.loaded / e.total) * 100)
                    );
                }
            });
            xhr.onreadystatechange = () => {
                if (xhr.readyState !== 4) return;
                if (xhr.status >= 200 && xhr.status < 300) {
                    item.progress = 100;
                    item.status = "done";
                    item.error = "";
                    this.uploader.uploadedCount++;
                    this._uploadNext();
                } else {
                    let msg = "Upload failed.";
                    try {
                        const data = JSON.parse(xhr.responseText);
                        if (data?.message) msg = data.message;
                        if (data?.errors) {
                            const first = Object.values(data.errors)[0];
                            if (Array.isArray(first) && first[0])
                                msg = first[0];
                        }
                    } catch {}
                    item.status = "error";
                    item.error = msg;
                    this._uploadNext();
                }
            };
            xhr.send(fd);
        },
    };
}

export default mediaLib;
if (typeof window !== "undefined") window.mediaLib = mediaLib;
