import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny, local } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                // InterVariable from rsms.me, cut down to latin and weights 400–600 with
                // fonttools; keeps the character variants that Google's and Bunny's copies drop.
                local('Inter', {
                    variants: [{ src: 'resources/fonts/InterVariable.woff2', weight: '400 600' }],
                    variable: '--font-inter',
                }),
                bunny('Figtree', {
                    weights: [500, 600],
                    variable: '--font-figtree',
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
