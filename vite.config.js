import { resolve } from 'path';
import { defineConfig } from 'vite';

const BUILD_DIR = resolve(__dirname, 'dist');

export default defineConfig({
  base: './',
  build: {
    assetsDir: '_dev/assets',
    emptyOutDir: true,
    outDir: BUILD_DIR,
    rollupOptions: {
      input: {
        app:      resolve('_dev/app.js'),
        checkout: resolve('_dev/js/checkout.js'),
        product:  resolve('_dev/js/product-gallery.js'),
        shop:     resolve('_dev/js/shop-filter.js'),
      },
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          const ext = assetInfo?.names?.[0]?.split('.').pop() || 'unknown';

          if (ext === 'css') {
            return 'main.css';
          }

          if (['woff', 'woff2', 'ttf', 'eot', 'otf'].includes(ext)) {
            return 'fonts/[name].[ext]';
          }

          return `[name].[ext]`;
        },
      },
    },
  },
});