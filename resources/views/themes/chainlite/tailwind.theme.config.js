/** @type {import('tailwindcss').Config} */
module.exports = {
    // Use class-based dark mode (toggle by adding `class="dark"` on <html> or <body>)
    darkMode: "class",

    // Keep the default CSS reset for public pages
    corePlugins: {
        preflight: true,
    },

    // Tailwind v4 reads sources from the `@source` directives in your CSS.
    // No `content: [...]` needed here.
    theme: {
        container: {
            center: true,
            padding: "1rem",
            screens: {
                sm: "640px",
                md: "768px",
                lg: "1024px",
                xl: "1280px",
                "2xl": "1536px",
            },
        },
        extend: {
            // Optional brand tokens (hooked to CSS variables so you can theme from CSS)
            colors: {
                brand: {
                    DEFAULT: "rgb(var(--brand) / <alpha-value>)",
                    foreground: "rgb(var(--brand-foreground) / <alpha-value>)",
                },
            },
            fontFamily: {
                // Example: add your preferred font stack
                sans: [
                    "Inter",
                    "ui-sans-serif",
                    "system-ui",
                    "Segoe UI",
                    "Roboto",
                    "Helvetica",
                    "Arial",
                    "Noto Sans",
                    "Apple Color Emoji",
                    "Segoe UI Emoji",
                    "Segoe UI Symbol",
                ],
            },
            borderRadius: {
                radius: "0.75rem",
                "2radius": "1rem",
            },
            boxShadow: {
                soft: "0 2px 12px 0 rgb(0 0 0 / 0.06)",
            },
        },
    },

    plugins: [
        // Keep empty to avoid extra deps. Add plugins here if you install any later.
        // require('@tailwindcss/typography'),
        // require('@tailwindcss/forms'),
        // require('@tailwindcss/aspect-ratio'),
    ],

    // OPTIONAL SAFETY SWITCHES (uncomment if needed):
    // prefix: 'tw-',           // <- use if you ever need to namespace utilities
    // corePlugins: { preflight: false }, // <- disable reset if embedding inside foreign markup
};
