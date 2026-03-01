import { resolve } from 'path';
import { defineConfig } from 'vite';

const JS_FILE = resolve('_dev/app.js');
const BUILD_DIR = resolve(__dirname, 'dist');

export default defineConfig({
  build: {
    assetsDir: '_dev/assets',
    emptyOutDir: true,
    outDir: BUILD_DIR,
    rollupOptions: {
      input: JS_FILE,
      output: {
        entryFileNames: 'app.js',
        chunkFileNames: 'app.js',
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