(function () {
    function bindInsertButton(el, editor) {
        var btn = document.getElementById("btn-insert-media");
        if (!btn) return;

        btn.addEventListener("click", async function () {
            if (typeof window.openMediaBrowser !== "function") {
                alert(
                    "Media Browser is not loaded. Make sure <x-media-browser /> is included in the layout."
                );
                return;
            }

            var files = await window.openMediaBrowser({ multiple: true });
            if (!files || !files.length) return;

            editor.model.change(function (writer) {
                files.forEach(function (file) {
                    var mime = (file.mime || "").toLowerCase();
                    var isImage = mime.indexOf("image/") === 0;

                    if (isImage) {
                        var img = writer.createElement("imageBlock", {
                            src: file.url,
                            alt: file.alt || file.title || "",
                        });
                        editor.model.insertContent(
                            img,
                            editor.model.document.selection
                        );
                    } else {
                        // Insert a simple link (no backticks / multiline)
                        var html =
                            '<p><a href="' +
                            file.url +
                            '" target="_blank" rel="noopener">' +
                            (file.filename || file.url) +
                            "</a></p>";
                        var viewFrag = editor.data.processor.toView(html);
                        var modelFrag = editor.data.toModel(viewFrag);
                        editor.model.insertContent(
                            modelFrag,
                            editor.model.document.selection
                        );
                    }
                });
            });
        });
    }

    // When any CKEditor instance becomes ready, wire the button
    window.addEventListener("ckeditor:ready", function (e) {
        bindInsertButton(e.detail.el, e.detail.editor);
    });

    // If editor might be ready before this script:
    var el = document.getElementById("content");
    if (el && el.__editor) bindInsertButton(el, el.__editor);
})();
