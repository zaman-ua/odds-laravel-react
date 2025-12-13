import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
    laravel({
      input: ['resources/js/app.jsx', 'resources/css/app.css'],
      refresh: true,
    }),
    react(),
    ],

  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,

    // важно для HMR, когда открываешь сайт по https://odds.lc
    hmr: {
      host: 'odds.lc',
      protocol: 'wss',
      clientPort: 443,
    },

    cors: true,
  },
});
