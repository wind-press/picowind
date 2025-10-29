# Picowind

> [!NOTE]
>
> Work in progress. Documentation may be incomplete.

A modern WordPress theme with multi-engine template support (Twig, Blade, Latte) built on Timber and Tailwind CSS.

## Requirements

- PHP >= 8.1
- Node.js >= 24.9.0 (recommended via Volta)
- Composer
- pnpm

## Getting Started

### 1. Clone Repository

Clone the repository to your `wp-content/themes` directory:

```bash
git clone https://github.com/wind-press/picowind picowind
cd picowind
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
pnpm install
```

### 3. Development

```bash
# Start Vite dev server with HMR
pnpm dev
```

The theme will be available at your WordPress site with hot module replacement enabled.

## Template Engines

Picowind supports three template engines:

### Twig (via Timber)
```php
Picowind\render('components/card.twig', ['title' => 'Hello']);
Picowind\render_string('<div>{{ title }}</div>', ['title' => 'Hello'], 'twig');
```

### Blade
```php
Picowind\render('components/button', ['text' => 'Click me']);
Picowind\render_string('<div>{{ $text }}</div>', ['text' => 'Hello'], 'blade');
```

### Latte
```php
Picowind\render('components/header.latte', ['title' => 'Welcome']);
Picowind\render_string('<div>{$title}</div>', ['title' => 'Hello'], 'latte');
```

## Shortcodes

Use template engines in WordPress content:

```
[twig template="components/card.twig"]
[twig]<div>{{ site.name }}</div>[/twig]

[blade template="components/button"]
[blade]<div>{{ $user->name }}</div>[/blade]

[latte template="components/header.latte"]
[latte]<div>{$post->title}</div>[/latte]
```

## Project Structure

```
picowind/
├── src/                    # PHP source code
│   ├── Core/              # Core framework classes
│   │   ├── Template.php   # Template rendering
│   │   └── Render/        # Engine implementations
│   ├── Supports/          # Feature support classes
│   └── functions.php      # Helper functions
├── views/                 # Template files
├── resources/             # Frontend source files
├── public/                # Static assets
└── blocks/                # Block editor blocks
```

## Available Functions

### Rendering
- `render($paths, $context, $engine, $print)` - Render template file
- `render_string($string, $context, $engine, $print)` - Render template string
- `context()` - Get global WordPress context

### Icons
- `iconify($name, $attributes)` - Render Iconify SVG icons

## Template Directories

Templates are loaded from multiple directories in order:
1. `views/` - Theme templates
2. Child theme directories (if applicable)
3. Parent theme directories

## Service Discovery

Picowind uses PHP attributes for automatic service registration:

```php
use Picowind\Core\Discovery\Attributes\Service;
use Picowind\Core\Discovery\Attributes\Hook;

#[Service]
class MyFeature
{
    #[Hook('init', type: 'action')]
    public function my_powerful_feature(): void
    {
        // Your code here
    }
}
```
