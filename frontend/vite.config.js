import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  // Nginx blocks hidden paths; keep pre-bundled dependencies on a public module path.
  cacheDir: 'node_modules/vite-cache',
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
  },
});
