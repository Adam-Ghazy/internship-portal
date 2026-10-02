import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/View/Components/**/*.php',
    ],
    theme: {
        extend: {
            colors: {
                // Palet brand INKA (merah, hitam, putih) dari mockup V3.
                // 'primary' = merah kontras 5.44:1 untuk teks putih; bukan nilai resmi brand-guide.
                primary: {
                    DEFAULT: '#d60008',
                    hover: '#a60b25',
                    accent: '#ff0009',
                    soft: '#fff0f2',
                },
                ink: '#24242a',
                muted: '#64646c',
                line: '#e3e3e6',
                surface: '#f8f8f8',
                paper: '#ffffff',
            },
            fontFamily: {
                sans: ['"IBM Plex Sans"', 'ui-sans-serif', 'system-ui', 'Arial', 'sans-serif'],
            },
            maxWidth: {
                content: '1360px',
            },
            borderRadius: {
                card: '10px',
            },
        },
    },
    plugins: [forms, typography],
};
