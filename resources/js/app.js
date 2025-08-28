import "./bootstrap";

// Penguin UI
import "penguinui";
if (window?.PenguinUI) window.PenguinUI.init();

// AlpineJS (used by your x-data / x-* directives)
import Alpine from "alpinejs";
import focus from "@alpinejs/focus"; // optional, improves a11y for menus, x-trap etc.

Alpine.plugin(focus);
window.Alpine = Alpine;
Alpine.start();
