import { build } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

async function runBuild() {
  console.log('Starting Manual Build Process...');
  try {
    await build({
      // Disable loading external config files to avoid "Access is denied"
      configFile: false,
      root: __dirname,
      base: '',
      plugins: [react()],
      define: {
        'process.env': {}
      },
      resolve: {
        alias: {
          '@': path.resolve(__dirname, 'src'),
        },
      },
      build: {
        outDir: 'dist',
        emptyOutDir: true,
        minify: 'esbuild',
        rollupOptions: {
          input: path.resolve(__dirname, 'index.html'),
        }
      },
      // Provide raw compiler options to esbuild directly
      esbuild: {
        tsconfigRaw: {
          compilerOptions: {
            jsx: 'react-jsx',
            target: 'esnext',
            moduleResolution: 'node',
            allowSyntheticDefaultImports: true,
          }
        }
      }
    });
    console.log('--- BUILD SUCCESSFUL ---');
  } catch (error) {
    console.error('--- BUILD FAILED ---');
    console.error(error);
    process.exit(1);
  }
}

runBuild();
