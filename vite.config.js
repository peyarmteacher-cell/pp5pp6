import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

export default defineConfig({
  // Strictly set root to current directory to stop esbuild from scanning parents
  root: './',
  base: './',
  // Use a local cache directory instead of node_modules to avoid permission issues
  cacheDir: './.vite_cache',
  plugins: [react()],
  resolve: {
    alias: {
      '@': '/src',
    },
  },
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    // Disable minification if it causes issues, or use 'esbuild'
    minify: 'esbuild',
    rollupOptions: {
      input: './index.html',
    }
  },
});
