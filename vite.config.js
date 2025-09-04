import { defineConfig, loadEnv } from 'vite'
import tailwindcss from "@tailwindcss/vite";
import FullReload from 'vite-plugin-full-reload'

const dest = './theme/assets/dist'
const entries = [
  './theme/assets/main.js',
  './theme/assets/styles/editor-style.css',
]

export default defineConfig(({ mode }) => {
  return {
    base: './',
    resolve: {
      alias: {
        '@': __dirname
      }
    },
    server: {
      cors: true,
      strictPort: true,
      port: 3001,
      https: false,
      hmr: { host: 'localhost', port: 3001 },
      watch: {
        // no polling
        awaitWriteFinish: {
          stabilityThreshold: 250,
          pollInterval: 100,
        },
        ignored: [
          '**/.git/**',
          '**/.DS_Store',
          '**/*.swp',
          '**/*.tmp',
          '**/.idea/**',
          '**/.vscode/**',
          'theme/assets/dist/**', // don't watch build output
        ],
      },
    },
    build: {
      outDir: dest,
      emptyOutDir: true,
      manifest: true,
      target: 'es2018',
      rollupOptions: {
        input: entries,
      },
      minify: true,
      write: true
    },
    plugins: [
      tailwindcss(),
      FullReload(
        [
          './theme/**/*.php',
          './theme/views/**/*.twig',
          './theme/blocks/**/*.twig',
          './theme/components/**/*.twig',
        ]
      ),
    ],
  }
})
