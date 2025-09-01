// resources/js/media.js
import * as FilePond from "filepond";
import FilePondPreview from "filepond-plugin-image-preview";
import FilePondType from "filepond-plugin-file-validate-type";
import FilePondSize from "filepond-plugin-file-validate-size";

import "filepond/dist/filepond.min.css";
import "filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css";

FilePond.registerPlugin(FilePondPreview, FilePondType, FilePondSize);

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content || "";

function mediaLib() {
    return {
        // grid
        items: [],
        nextPageUrl: null,
        loading: false,

        // filters UI
        filters: {
            q: "",
            term_taxonomy_id: "",
            images_only: true,
            sort: "newest",
        },

        // categories for filters & quick-create
        categories: [],
        categoriesLoading: false,

        // toast
        toast: { open: false, type: "info", msg: "" },
        showToast(msg, type = "info") {
            this.toast.msg = msg;
            this.toast.type = type;
            this.toast.open = true;
            setTimeout(() => (this.toast.open = false), 3000);
        },

        // inspector/editor
        selectedId: null,
        inspector: { open: false, item: null },
        editor: { name: "", alt: "", caption: "", term_taxonomy_id: "" },

        // upload modal
        upload: {
            open: false,
            pond: null,
            uploading: false,
            errors: {
                newCategoryName: "",
                newCategoryParent: "",
                files: "",
            },
            newCategoryName: "",
            newCategoryParent: "",
            creating: false,
        },

        // lifecycle
        init() {
            this.fetchCategories();
            this.fetch();
            this.$watch("filters", () => this.fetch(), { deep: true });
        },

        // -------------- API helpers --------------
        apiList(params = {}) {
            const clean = {
                q: this.filters.q ?? "",
                term_taxonomy_id: this.filters.term_taxonomy_id ?? "",
                images_only: this.filters.images_only ? 1 : 0,
                sort: this.filters.sort ?? "newest",
                ...params,
            };
            const qs = new URLSearchParams(clean).toString();
            return fetch(`/admin/media/list?${qs}`, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            }).then((r) => r.json());
        },

        fetch() {
            this.loading = true;
            this.apiList()
                .then((data) => {
                    this.items = data?.data || [];
                    this.nextPageUrl = data?.next_page_url || null;
                })
                .catch(() => this.showToast("Failed to load media.", "error"))
                .finally(() => (this.loading = false));
        },

        loadMore() {
            if (!this.nextPageUrl) return;
            fetch(this.nextPageUrl, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            })
                .then((r) => r.json())
                .then((data) => {
                    this.items.push(...(data?.data || []));
                    this.nextPageUrl = data?.next_page_url || null;
                })
                .catch(() => this.showToast("Failed to load more.", "error"));
        },

        fetchCategories() {
            this.categoriesLoading = true;
            fetch("/admin/media/categories/json", {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            })
                .then((r) => r.json())
                .then((data) => (this.categories = data || []))
                .catch(() =>
                    this.showToast("Failed to load categories.", "error")
                )
                .finally(() => (this.categoriesLoading = false));
        },

        // -------------- utils --------------
        human(bytes) {
            if (bytes == null) return "";
            const units = ["B", "KB", "MB", "GB"];
            let i = 0;
            let v = Number(bytes);
            while (v >= 1024 && i < units.length - 1) {
                v /= 1024;
                i++;
            }
            return `${v.toFixed(1)} ${units[i]}`;
        },

        // -------------- inspector --------------
        openInspector(item) {
            this.selectedId = item.id;
            this.inspector.item = item;
            this.editor.name = item.title || item.filename || "";
            this.editor.alt = item.alt || "";
            this.editor.caption = item.caption || "";
            this.editor.term_taxonomy_id = item.category?.id || "";
            this.inspector.open = true;
        },

        saveMeta() {
            const id = this.inspector.item.id;
            fetch(`/admin/media/meta/${id}`, {
                method: "PATCH",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf(),
                },
                body: JSON.stringify(this.editor),
            })
                .then((r) => r.json())
                .then(() => {
                    this.showToast("Saved.", "success");
                    this.fetch();
                })
                .catch(() => this.showToast("Save failed.", "error"));
        },

        replace(e) {
            const id = this.inspector.item.id;
            const fd = new FormData();
            if (e.target.files?.[0]) fd.append("files[0]", e.target.files[0]);
            fetch(`/admin/media/replace/${id}`, {
                method: "POST",
                headers: { "X-CSRF-TOKEN": csrf() },
                body: fd,
            })
                .then((r) => r.json())
                .then(() => {
                    this.showToast("File replaced.", "success");
                    this.fetch();
                })
                .catch(() => this.showToast("Replace failed.", "error"));
        },

        trash() {
            const id = this.inspector.item.id;
            fetch(`/admin/media/${id}`, {
                method: "DELETE",
                headers: { "X-CSRF-TOKEN": csrf() },
            })
                .then((r) => r.json())
                .then(() => {
                    this.inspector.open = false;
                    this.showToast("Moved to trash.", "success");
                    this.fetch();
                })
                .catch(() => this.showToast("Delete failed.", "error"));
        },

        // -------------- upload modal --------------
        openUpload() {
            this.upload.open = true;

            this.$nextTick(() => {
                if (this.upload.pond) return;

                const input = this.$refs.pondInput;
                if (!input) return;

                this.upload.pond = FilePond.create(input, {
                    credits: false,
                    allowMultiple: true,
                    allowReorder: true,
                    instantUpload: false, // we will trigger with a button
                    allowProcess: false, // hide FilePond's own process button
                    maxFileSize: "20MB",
                    acceptedFileTypes: [
                        "image/*",
                        "video/mp4",
                        "video/webm",
                        "application/pdf",
                    ],

                    // Smaller previews
                    imagePreviewHeight: 110,
                    imagePreviewMaxHeight: 140,
                    stylePanelLayout: "integrated", // compact card layout
                    // nicer dnd label
                    labelIdle: `<div class="text-sm">
               <div class="font-medium mb-1">Drag & drop files</div>
               <div class="text-xs text-slate-500">or <span class="text-blue-600">browse</span> from your device</div>
             </div>`,

                    server: {
                        process: {
                            url: "/admin/media/upload",
                            method: "POST",
                            headers: { "X-CSRF-TOKEN": csrf() },
                            ondata: (formData) => {
                                if (this.filters.term_taxonomy_id) {
                                    formData.append(
                                        "term_taxonomy_id",
                                        this.filters.term_taxonomy_id
                                    );
                                }
                                return formData;
                            },
                        },
                    },
                });

                // Disable buttons during processing
                input.addEventListener("FilePond:processfilestart", () => {
                    this.upload.uploading = true;
                });
                input.addEventListener("FilePond:processfiles", () => {
                    this.upload.uploading = false;
                    this.closeUpload();
                    this.fetch();
                    this.showToast("Uploaded.", "success");
                });
                input.addEventListener("FilePond:error", () => {
                    this.upload.uploading = false;
                    this.upload.errors.files =
                        "Upload failed. Check files and try again.";
                });
            });
        },

        // Button in footer
        processUpload() {
            if (this.upload.pond) {
                if (!this.upload.pond.getFiles().length) {
                    this.upload.errors.files = "Please add at least one file.";
                    return;
                }
                this.upload.errors.files = "";
                this.upload.pond.processFiles();
            }
        },

        clearUpload() {
            if (this.upload.pond) this.upload.pond.removeFiles();
            this.upload.errors.files = "";
        },

        closeUpload() {
            this.upload.open = false;
            if (this.upload.pond) {
                this.upload.pond.destroy();
                this.upload.pond = null;
            }
            // reset
            this.upload.uploading = false;
            this.upload.errors = {
                newCategoryName: "",
                newCategoryParent: "",
                files: "",
            };
            this.upload.newCategoryName = "";
            this.upload.newCategoryParent = "";
        },

        // quick-create category inside upload modal
        createCategory() {
            this.upload.creating = true;
            this.upload.errors = {
                newCategoryName: "",
                newCategoryParent: "",
                files: "",
            };

            fetch("/admin/media/categories/quick", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf(),
                },
                body: JSON.stringify({
                    name: this.upload.newCategoryName,
                    parent_id: this.upload.newCategoryParent || null,
                }),
            })
                .then(async (r) => {
                    if (!r.ok) {
                        const data = await r.json().catch(() => ({}));
                        const errs = data?.errors || {};
                        this.upload.errors.newCategoryName =
                            (errs.name && errs.name[0]) || "";
                        this.upload.errors.newCategoryParent =
                            (errs.parent_id && errs.parent_id[0]) || "";
                        throw new Error("Validation failed");
                    }
                    return r.json();
                })
                .then((data) => {
                    this.showToast("Category created.", "success");
                    this.fetchCategories();
                    if (data?.item?.id) {
                        this.filters.term_taxonomy_id = String(data.item.id);
                    }
                    this.upload.newCategoryName = "";
                    this.upload.newCategoryParent = "";
                })
                .catch(() => {
                    if (!this.upload.errors.newCategoryName) {
                        this.showToast("Create failed.", "error");
                    }
                })
                .finally(() => (this.upload.creating = false));
        },
    };
}

export default mediaLib;
if (typeof window !== "undefined") window.mediaLib = mediaLib;
