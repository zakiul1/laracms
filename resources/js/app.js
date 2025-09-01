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
import mediaLib from "./media"; // <-- make sure this path is correct
Alpine.data("mediaLib", mediaLib); // <-- registers the component by name

// 4) Expose & start Alpine (now that components are registered)
window.Alpine = Alpine;
if (!window.Alpine.__started) {
    try {
        window.Alpine.start();
        window.Alpine.__started = true;
    } catch (_) {}
}

// 5) Lucide (after Alpine; non-blocking)
import { createIcons, icons } from "lucide";
const bootIcons = () => {
    try {
        createIcons({ icons });
    } catch (_) {}
};
const idle = (fn) =>
    window.requestIdleCallback ? requestIdleCallback(fn) : setTimeout(fn, 0);
document.readyState === "loading"
    ? document.addEventListener("DOMContentLoaded", () => idle(bootIcons))
    : idle(bootIcons);
["turbo:load", "inertia:finish"].forEach((evt) =>
    document.addEventListener(evt, () => idle(bootIcons))
);
if (import.meta?.hot)
    import.meta.hot.on("vite:afterUpdate", () => idle(bootIcons));
