import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

const apiProxyTarget = process.env.VITE_API_PROXY_TARGET || 'http://127.0.0.1:8010'
const devPort = Number(process.env.VITE_APP_PORT || process.env.VITE_PORT || 5173)

export default defineConfig({
  plugins: [vue()],
  server: {
    port: devPort,
    strictPort: false,
    proxy: {
      '/api': {
        target: apiProxyTarget,
        changeOrigin: true,
      }
    }
  },
  build: {
    outDir: 'dist',
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['vue', 'vue-router', 'pinia'],
          charts: ['chart.js', 'vue-chartjs'],
          leaflet: ['leaflet'],
        }
      }
    }
  }
})
