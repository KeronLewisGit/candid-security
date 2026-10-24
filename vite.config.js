import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny, local } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Fonts are bundled at build time and served from /build, so visitors never hit a font CDN.
// Archivo is a grotesque with a width axis (62–125%): expanded + extra-bold reads like the
// lettering on a uniform patch or an armoured truck door. Work Sans carries the reading text,
// JetBrains Mono the badge numbers, references and station labels.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                // Variable file (wght 100–900, wdth 62–125), latin subset from Google Fonts (OFL).
                local('Archivo', {
                    alias: 'display',
                    variable: '--ff-display',
                    variants: [{ src: 'resources/fonts/archivo-variable-latin.woff2', weight: '100 900' }],
                    preload: true,
                    fallbacks: ['Arial Black', 'Arial', 'ui-sans-serif', 'sans-serif'],
                }),
                bunny('Work Sans', {
                    alias: 'sans',
                    variable: '--ff-sans',
                    weights: [400, 500, 600, 700],
                    subsets: ['latin'],
                    preload: [{ weight: 400 }, { weight: 600 }],
                    fallbacks: ['ui-sans-serif', 'system-ui', 'sans-serif'],
                }),
                bunny('JetBrains Mono', {
                    alias: 'mono',
                    variable: '--ff-mono',
                    weights: [500],
                    subsets: ['latin'],
                    fallbacks: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: { watch: { ignored: ['**/storage/framework/views/**'] } },
});
