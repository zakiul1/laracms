// tailwind.config.js
import forms from "@tailwindcss/forms";
import typography from "@tailwindcss/typography";
import penguinui from "penguinui/plugin";

export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.{js,ts,vue,jsx,tsx}",
        "./storage/framework/views/*.php",
        "./node_modules/penguinui/dist/**/*.js", // << scan Penguin UI
    ],
    theme: {
        extend: {
            // Customize brand colors, fonts, etc. if needed
        },
    },
    plugins: [
        forms,
        typography,
        penguinui, // << Penguin UI Tailwind plugin
    ],
};
