import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Saberé Brand Colors
                sabere: {
                    dark: '#1A2A42',      // Primary dark blue
                    light: '#F8F5F0',     // Light cream/beige
                    accent: '#27C2A5',    // Teal/mint green
                    purple: '#7854D2',    // Purple accent
                },
            },
        },
    },

    plugins: [forms],
};
