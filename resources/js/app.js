// resources/js/app.js

// 1) Laravel bootstrap
import "./bootstrap";

// 2) Alpine + plugins
import Alpine from "alpinejs";
import focus from "@alpinejs/focus";
import collapse from "@alpinejs/collapse";
Alpine.plugin(focus);
Alpine.plugin(collapse);

// 3) Register page components BEFORE starting Alpine
import mediaLib from "./media"; // make sure this path is correct
Alpine.data("mediaLib", mediaLib);

// 3.1) Widgets admin + Settings factories (must be loaded before Alpine starts)
import "./widgets-admin";
import "./settings";

// 4) Conditionally preload page-specific modules that define globals used by x-data
//    This must happen BEFORE Alpine.start(), otherwise x-data="menuEditor()"
//    would be evaluated before window.menuEditor is defined.
async function preloadPageModules() {
    const needsMenuEditor = !!document.getElementById("menu-root");
    if (needsMenuEditor) {
        // defines: window.menuEditor = () => ({ ... })
        await import("./admin/menus-edit.js");
    }
}

// 5) Start Alpine AFTER preloading any page modules that are required
(async () => {
    await preloadPageModules();

    window.Alpine = Alpine;
    if (!window.Alpine.__started) {
        try {
            await Alpine.start();
            window.Alpine.__started = true;
        } catch (_) {
            /* no-op */
        }
    }

    // 6) Lucide (after Alpine; non-blocking)
    //    Create icons lazily to avoid blocking hydration
    try {
        const { createIcons, icons } = await import("lucide");
        const bootIcons = () => {
            try {
                createIcons({ icons });
            } catch (_) {}
        };
        const idle = (fn) =>
            window.requestIdleCallback
                ? requestIdleCallback(fn)
                : setTimeout(fn, 0);

        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", () =>
                idle(bootIcons)
            );
        } else {
            idle(bootIcons);
        }

        ["turbo:load", "inertia:finish"].forEach((evt) =>
            document.addEventListener(evt, () => idle(bootIcons))
        );

        if (import.meta?.hot) {
            import.meta.hot.on("vite:afterUpdate", () => idle(bootIcons));
        }
    } catch (_) {
        /* lucide optional */
    }
})();
