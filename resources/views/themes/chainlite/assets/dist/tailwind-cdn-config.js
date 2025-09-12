// Configure Tailwind CDN for this theme.
// We use the same "cf-" prefix your templates already use.
// NOTE: Loaded BEFORE https://cdn.tailwindcss.com
window.tailwind = window.tailwind || {};
window.tailwind.config = {
    prefix: "cf-",
    theme: {
        extend: {
            fontFamily: {
                sans: [
                    "ui-sans-serif",
                    "system-ui",
                    "-apple-system",
                    "Segoe UI",
                    "Roboto",
                    "Inter",
                    "Helvetica",
                    "Arial",
                    "Apple Color Emoji",
                    "Segoe UI Emoji",
                ],
            },
        },
    },
};
// Optional: You can add more config here if you need (colors, etc.)
