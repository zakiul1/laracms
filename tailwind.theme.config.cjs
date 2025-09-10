module.exports = {
    content: [
        "./resources/views/themes/chainlite/**/*.blade.php",
        // add other theme view dirs if any
    ],
    // Prefix to avoid collisions with admin/utilities/plugins
    prefix: "tw-",
    // If preflight breaks 3rd party embeds, set to false
    corePlugins: { preflight: true },
    theme: { extend: {} },
    plugins: [
        require("@tailwindcss/typography"),
        require("@tailwindcss/forms"),
    ],
};
