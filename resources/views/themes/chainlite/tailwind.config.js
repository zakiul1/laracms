// tailwind v4
const path = require("path");

module.exports = {
    content: [
        // your theme files (works whether you keep them in /views or not)
        path.join(__dirname, "views/**/*.blade.php"),
        path.join(__dirname, "partials/**/*.blade.php"),
        path.join(__dirname, "**/*.blade.php"),
        path.join(__dirname, "**/*.php"), // if you output classes in functions.php, etc.
    ],
    // no prefix
};
