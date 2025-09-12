// vite.config.js
import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), "");
    const disableOverlay = env.VITE_DISABLE_HMR_OVERLAY === "1";

    return {
        plugins: [
            tailwindcss(), // Tailwind v4
            laravel({
                // ✅ Inputs at the REAL paths
                input: [
                    // Admin bundles (keep if you use them)
                    "resources/css/app.css",
                    "resources/js/app.js",

                    // Theme CSS (Tailwind entry)
                    "resources/views/themes/chainlite/src/theme.css",

                    // If you have a theme JS entry, uncomment next line and ensure the file exists:
                    // "resources/views/themes/chainlite/src/theme.js",
                ],
                // Refresh on changes (this already covers the theme under views/)
                refresh: [
                    "routes/**",
                    "app/Http/Controllers/**",
                    "resources/views/**",
                ],
            }),
        ],
        server: {
            hmr: {
                overlay: !disableOverlay, // set VITE_DISABLE_HMR_OVERLAY=1 in .env to hide overlay
            },
        },
        css: {
            devSourcemap: true,
        },
    };
});
