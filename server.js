/**
 * Bridge file for Plesk Node.js
 * This file allows running server.ts using tsx
 */
import('tsx/esm').then(() => {
  import('./server.ts');
}).catch(err => {
  console.error('Failed to start server:', err);
});
