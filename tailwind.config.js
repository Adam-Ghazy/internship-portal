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
                // Palet brand INKA — nilai warna disamakan persis dengan mockup/prototype V3
                // (--ink #24242a, --muted #64646c, --line #e3e3e6, --bg #f8f8f8, --green #226047, --amber #795419).
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
                navy: '#16213a',
                info: '#3b5bdb',
                'info-soft': '#eef2ff',
                success: '#226047',
                'success-soft': '#edf6f0',
                warning: '#795419',
                'warning-soft': '#f6efe3',
            },
            fontFamily: {
                sans: ['"IBM Plex Sans"', 'ui-sans-serif', 'system-ui', 'Arial', 'sans-serif'],
            },
            maxWidth: {
                content: '1360px',
            },
            borderRadius: {
                card: '12px',
            },
        },
    },
    plugins: [forms, typography],
};
