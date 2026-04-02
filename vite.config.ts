import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import path from 'path';
import { fileURLToPath } from 'url';
import { defineConfig, loadEnv } from 'vite';

// Get absolute path of current directory
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export default defineConfig(({ mode }) => {
  // Use absolute path for root to prevent esbuild from walking up to restricted directories
  const rootPath = __dirname;
  const env = loadEnv(mode, rootPath, '');
  
  return {
    root: rootPath,
    base: './',
    // Explicitly set cache directory inside the project
    cacheDir: path.resolve(rootPath, 'node_modules/.vite'),
    plugins: [react(), tailwindcss()],
    build: {
      outDir: path.resolve(rootPath, 'dist'),
      emptyOutDir: true,
      // Ensure rollup doesn't try to access external files
      rollupOptions: {
        input: path.resolve(rootPath, 'index.html'),
      },
    },
    define: {
      'process.env.GEMINI_API_KEY': JSON.stringify(env.GEMINI_API_KEY),
    },
    resolve: {
      alias: {
        '@': path.resolve(rootPath, 'src'),
      },
    },
    server: {
      hmr: process.env.DISABLE_HMR !== 'true',
    },
  };
});
