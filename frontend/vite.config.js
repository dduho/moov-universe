import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'
import path from 'path'

// Désactiver PWA si VITE_DISABLE_PWA est défini
const disablePWA = process.env.VITE_DISABLE_PWA === 'true'

// https://vite.dev/config/
export default defineConfig({
  server: {
    hmr: {
      clientPort: 5173
    }
  },
  plugins: [
    vue(),
    !disablePWA && VitePWA({
      strategies: 'injectManifest',
      srcDir: 'public',
      filename: 'service-worker.js',
      injectManifest: {
        injectionPoint: undefined
      },
      includeAssets: ['favicon.ico', 'apple-touch-icon.svg', 'icon.svg'],
      manifest: {
        name: 'Moov Universe - Gestion PDV',
        short_name: 'Moov Universe',
        description: 'Application de gestion des Points de Vente Moov',
        theme_color: '#FF6600',
        background_color: '#ffffff',
        display: 'standalone',
        orientation: 'portrait-primary',
        scope: '/',
        start_url: '/',
        categories: ['business', 'productivity'],
        icons: [
          {
            src: 'pwa-192x192.svg',
            sizes: '192x192',
            type: 'image/svg+xml',
            purpose: 'any'
          },
          {
            src: 'pwa-512x512.svg',
            sizes: '512x512',
            type: 'image/svg+xml',
            purpose: 'any'
          },
          {
            src: 'pwa-maskable-192x192.svg',
            sizes: '192x192',
            type: 'image/svg+xml',
            purpose: 'maskable'
          },
          {
            src: 'pwa-maskable-512x512.svg',
            sizes: '512x512',
            type: 'image/svg+xml',
            purpose: 'maskable'
          }
        ]
      },
      devOptions: {
        enabled: false
      }
    })
  ].filter(Boolean),
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
})
