import { resolve } from 'node:path';
import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';

const theme = resolve(import.meta.dirname, 'wp-content/themes/stiqon');

// Builds the theme's stylesheet and script from assets/src into assets/build.
// Fonts come from @fontsource packages and are bundled, so neither the build nor
// the browser ever asks a font CDN. inc/assets.php reads the manifest written here.
export default defineConfig({
    root: theme,
    // Relative addresses: the theme directory's URL differs between installations.
    base: './',
    plugins: [tailwindcss()],
    build: {
        outDir: resolve(theme, 'assets/build'),
        emptyOutDir: true,
        manifest: 'manifest.json',
        rollupOptions: {
            input: [
                resolve(theme, 'assets/src/css/app.css'),
                resolve(theme, 'assets/src/css/shop.css'),
                resolve(theme, 'assets/src/js/app.js'),
            ],
        },
    },
});
