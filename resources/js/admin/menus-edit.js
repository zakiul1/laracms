// resources/js/admin/menus-edit.js
import Sortable from "sortablejs";

/* toast helper */
function toast(msg, ok = true) {
    const el = document.createElement("div");
    el.textContent = msg;
    el.className =
        "fixed top-4 right-4 z-[100] px-3 py-2 rounded-md shadow " +
        (ok ? "bg-green-600 text-white" : "bg-red-600 text-white");
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 2000);
}

/* ----- nesting helpers ----- */
function serializeList(ul) {
    const out = [];
    ul.querySelectorAll(":scope > li").forEach((li) => {
        const node = { id: Number(li.dataset.id), children: [] };
        const child = li.querySelector(":scope > ul.children");
        if (child) node.children = serializeList(child);
        out.push(node);
    });
    return out;
}

function makeSortable(ul) {
    new Sortable(ul, {
        group: { name: "menu", pull: true, put: true },
        animation: 150,
        handle: ".drag-handle",
        draggable: "> li",
        // IMPORTANT: single tokens only
        ghostClass: "sortable-ghost",
        chosenClass: "sortable-chosen",
        dragClass: "sortable-drag",
        fallbackOnBody: true,
        swapThreshold: 0.65,
        emptyInsertThreshold: 8,
    });
}

function initAllSortables() {
    const root = document.getElementById("menu-root");
    if (!root) return;
    makeSortable(root);
    root.querySelectorAll("ul.children").forEach(makeSortable);
}

/* ----- Alpine component ----- */
function menuEditor() {
    return {
        save() {
            const ul = document.getElementById("menu-root");
            const url = ul.dataset.reorderUrl;
            const token = ul.dataset.csrf;
            const tree = serializeList(ul);

            fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": token,
                    Accept: "application/json",
                },
                body: JSON.stringify({ tree }),
            })
                .then((r) => (r.ok ? r.json() : Promise.reject()))
                .then(() => toast("Menu order saved."))
                .catch(() => toast("Failed to save order", false));
        },
    };
}

// expose for x-data="menuEditor()" and register for x-data="menuEditor"
window.menuEditor = menuEditor;
function registerWithAlpine() {
    if (window.Alpine?.data) {
        window.Alpine.data("menuEditor", menuEditor);
        document
            .querySelectorAll('[x-data="menuEditor"], [x-data="menuEditor()"]')
            .forEach((el) => window.Alpine.initTree(el));
    } else {
        window.addEventListener("alpine:init", () => {
            window.Alpine.data("menuEditor", menuEditor);
        });
    }
}
registerWithAlpine();

document.addEventListener("DOMContentLoaded", () => {
    initAllSortables();
    if (window.createIcons && window.icons) {
        (window.requestIdleCallback || setTimeout)(() =>
            window.createIcons({ icons: window.icons })
        );
    }
});
