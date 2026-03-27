import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class', // Enable class-based dark mode
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Custom Spectora Palette based on User Request + Legacy Analysis
                spectora: {
                    bg: '#0F172A',       // Navy (User requested)
                    'bg-dark': '#070B10', // Legacy Darker BG
                    card: '#1E293B',     // Slate-800 for cards
                    cyan: '#38BDF8',     // Cyan (User requested)
                    violet: '#8B5CF6',   // Legacy Primary
                }
            }
        },
    },

    plugins: [forms],
};
