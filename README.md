# TimberKit 🚀
A lean WordPress starter theme with [Timber](https://timber.github.io/docs/v2/) (Twig), [ACF Pro Blocks](https://www.advancedcustomfields.com/resources/blocks/), [Vite](https://vite.dev/), [Tailwind v4.0](https://tailwindcss.com/), [Daisy UI](https://daisyui.com/) and [Alpine.js](https://alpinejs.dev/). Includes a shared base block wrapper, small component partials, an inline-SVG helper, and practical editor CSS so Gutenberg previews look right.

## 🧱 Stack

Timber • ACF Pro Blocks • Vite • Tailwind v4 • DaisyUI • Alpine.js

## ✨ Highlights
- ⚡ Vite dev server + hashed production assets
- 🧱 Shared block base: ACF blocks extend one wrapper for consistent section layout + appearance controls
- 🎨 Tailwind v4 + DaisyUI (theme: light) + Typography plugin for rich text
- 🧩 Small component library (Heading, Rich Text, Button) with “expects” notes
- 🖼️ Safe inline SVG helper (optional monochrome via `currentColor`)
- 🧭 Primary/Footer menus + Theme Settings (ACF Options) in Twig context
- ✍️ Scoped editor CSS so utilities render predictably in Gutenberg

## Requirements
- WordPress 6.x
- PHP 8.1+
- Composer, Node 18+

## 🚀 Install
```
# in wp-content/themes
git clone <repo> timberkit && cd timberkit
composer install
npm install
```
Activate TimberKit in WP. Install/activate ACF Pro.

## 🧑‍💻 Develop
```
# one-time: build CSS for editor previews
npm run build

# dev server (HMR)
npm run dev
```

## 🏭 Production
```
npm run build
```
Vite outputs hashed files; the theme reads the manifest automatically.
To test production locally, set vite.environment = "production" in config.json.

## 🗂️ Structure
```
theme/
  assets/           # Vite entry, styles
  blocks/           # ACF blocks (extend shared base)
  components/       # Heading, Rich Text, Button
  views/            # base.twig, header.twig, footer.twig
```

## 🙌 Attribution
Inspired by `cearls/timberland` (MIT). View their full repo [here](https://github.com/cearls/timberland).

## 📄 License
MIT © Jacob Tarr
