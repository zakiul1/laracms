import ClassicEditor from "@ckeditor/ckeditor5-build-classic";

/**
 * Minimal upload adapter for CKEditor 5.
 * Expects the server to return: { url: "https://..." }
 */
class LaravelUploadAdapter {
    constructor(loader, uploadUrl, csrf) {
        this.loader = loader;
        this.uploadUrl = uploadUrl;
        this.csrf = csrf;
    }

    upload() {
        return this.loader.file.then((file) => {
            const data = new FormData();
            data.append("upload", file);

            return fetch(this.uploadUrl, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": this.csrf,
                },
                body: data,
                credentials: "same-origin",
            })
                .then(async (res) => {
                    if (!res.ok) throw new Error(await res.text());
                    return res.json();
                })
                .then((json) => {
                    if (!json.url)
                        throw new Error("Upload response missing url");
                    return { default: json.url };
                });
        });
    }

    abort() {
        // no-op; the fetch API doesn’t support abort cleanly here
    }
}

/**
 * Initialize editors for all .js-ckeditor elements.
 * The element can pass data-upload-url="..." to point the adapter.
 */
export function bootCkEditor() {
    const csrf =
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") || "";

    document.querySelectorAll(".js-ckeditor").forEach((el) => {
        const uploadUrl = el.dataset.uploadUrl || "/admin/ckeditor/upload";

        ClassicEditor.create(el, {
            // a sensible, compact toolbar (add/remove items as you like)
            toolbar: {
                items: [
                    "undo",
                    "redo",
                    "|",
                    "heading",
                    "|",
                    "bold",
                    "italic",
                    "underline",
                    "link",
                    "blockQuote",
                    "|",
                    "bulletedList",
                    "numberedList",
                    "|",
                    "insertTable",
                    "imageUpload",
                    "mediaEmbed",
                    "horizontalLine",
                    "removeFormat",
                ],
            },
            // image tools on click
            image: {
                toolbar: [
                    "imageTextAlternative",
                    "imageStyle:inline",
                    "imageStyle:block",
                    "imageStyle:side",
                ],
            },
            // connect our adapter
            extraPlugins: [
                (editor) => {
                    editor.plugins.get("FileRepository").createUploadAdapter = (
                        loader
                    ) => new LaravelUploadAdapter(loader, uploadUrl, csrf);
                },
            ],
        }).catch((err) => {
            // Don’t kill the page if one editor fails
            console.error("CKEditor init failed:", err);
        });
    });
}

// auto-boot when this file is imported
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", bootCkEditor);
} else {
    bootCkEditor();
}
