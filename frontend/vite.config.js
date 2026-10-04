import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'node:path';

export default defineConfig(({ command }) => ({
  plugins: [react()],
  base: command === 'build' ? './' : '/',
  server: {
    host: '127.0.0.1',
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:3000',
        changeOrigin: false,
      },
    },
  },
  build: {
    outDir: resolve(import.meta.dirname, '../public/admin'),
    emptyOutDir: true,
    rollupOptions: {
      input: {
        admin: resolve(import.meta.dirname, 'index.html'),
        account: resolve(import.meta.dirname, 'account/index.html'),
      },
    },
  },
}));
