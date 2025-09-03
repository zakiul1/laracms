import ClassicEditor from "@ckeditor/ckeditor5-build-classic";

/** ... LaravelUploadAdapter class (unchanged) ... **/

export function bootCkEditor() {
    const csrf =
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") || "";

    document.querySelectorAll(".js-ckeditor").forEach((el) => {
        const uploadUrl = el.dataset.uploadUrl || "/admin/ckeditor/upload";

        ClassicEditor.create(el, {
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
            image: {
                toolbar: [
                    "imageTextAlternative",
                    "imageStyle:inline",
                    "imageStyle:block",
                    "imageStyle:side",
                ],
            },
            extraPlugins: [
                (editor) => {
                    editor.plugins.get("FileRepository").createUploadAdapter = (
                        loader
                    ) => new LaravelUploadAdapter(loader, uploadUrl, csrf);
                },
            ],
        })
            .then((editor) => {
                // ✅ expose the instance for other scripts
                el.__editor = editor;
                window.dispatchEvent(
                    new CustomEvent("ckeditor:ready", {
                        detail: { el, editor },
                    })
                );
            })
            .catch((err) => {
                console.error("CKEditor init failed:", err);
            });
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", bootCkEditor);
} else {
    bootCkEditor();
}
