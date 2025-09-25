<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind\Supports;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;
use Symfony\Component\Finder\Finder;
use Throwable;
use WindPress\WindPress\Utils\Common;
use WindPress\WindPress\Utils\Config;

#[Service]
class WindPress
{
    public function __construct() {}

    #[Hook('f!windpress/core/cache:compile.providers', 'filter')]
    public function compile_providers(array $providers): array
    {
        $providers[] = [
            'id' => 'picowind',
            'name' => 'picowind Theme',
            'description' => 'Scans the picowind theme & child theme',
            'callback' => $this->provider_callback(...),
            'enabled' => Config::get(
                sprintf(
                    'integration.%s.enabled',
                    'picowind', // The id of this custom provider
                ),
                true,
            ),
            'type' => 'theme',
            'homepage' => 'https://picostrap.com/?ref=windpress',
            'is_installed_active' => static fn () => 1,
        ];

        return $providers;
    }

    public function provider_callback(): array
    {
        // Any files with this extension will be scanned
        $file_extensions = [
            'php',
            'js',
            'twig',
        ];

        $contents = [];

        // if the theme is not picowind or its' child, early return
        if (get_template() !== 'picowind') {
            return $contents;
        }

        $finder = new Finder();

        $wpTheme = wp_get_theme();
        $themeDir = $wpTheme->get_stylesheet_directory();

        // Check if the current theme is a child theme and get the parent theme directory
        $has_parent = (bool) $wpTheme->parent();

        if ($has_parent) {
            $parentThemeDir = $wpTheme->parent()->get_stylesheet_directory() ?? null;
        }

        // Scan the theme directory according to the file extensions
        foreach ($file_extensions as $file_extension) {
            $finder
                ->files()
                ->in($themeDir)
                ->notPath('vendor')
                ->name('*.' . $file_extension);
            if ($has_parent && $parentThemeDir) {
                $finder
                    ->files()
                    ->in($parentThemeDir)
                    ->notPath('vendor')
                    ->name('*.' . $file_extension);
            }
        }

        // Get the file contents and send to the compiler
        foreach ($finder as $file) {
            $contents[] = [
                'name' => $file->getRelativePathname(),
                'content' => $file->getContents(),
            ];
        }

        return $contents;
    }

    #[Hook('f!windpress/core/volume:get_entries.entries', 'filter')]
    public function sfs_handler_get(array $sfs_entries): array
    {
        $entries = [];

        $template_dir = get_template_directory();
        $directories = ['assets/styles', 'views', 'blocks', 'components'];

        $finder = new Finder();

        $existing_dirs = [];
        foreach ($directories as $directory) {
            $full_path = $template_dir . '/' . $directory;
            if (file_exists($full_path)) {
                $existing_dirs[] = $full_path;
            }
        }

        if ([] === $existing_dirs) {
            return $entries;
        }

        $finder
            ->ignoreUnreadableDirs()
            ->in($existing_dirs)
            ->files()
            ->followLinks()
            ->name(['*.css', '*.js']);

        do_action('a!picowind_sfs_handler_get:get_entries.finder', $finder);

        foreach ($finder as $file) {
            if (! is_readable($file->getPathname())) {
                continue;
            }

            // Detect directory based on file path
            $file_path = $file->getPathname();
            $relative_from_template = str_replace($template_dir . '/', '', $file_path);

            // Determine handler and relative_path based on directory
            if (str_starts_with($relative_from_template, 'assets/styles/')) {
                $handler = 'picowind-root';
                $relative_path = '@picowind/' . $file->getRelativePathname();
            } elseif (str_starts_with($relative_from_template, 'views/')) {
                $handler = 'picowind-views';
                $relative_path = '@picowind-views/' . $file->getRelativePathname();
            } elseif (str_starts_with($relative_from_template, 'blocks/')) {
                $handler = 'picowind-blocks';
                $relative_path = '@picowind-blocks/' . $file->getRelativePathname();
            } elseif (str_starts_with($relative_from_template, 'components/')) {
                $handler = 'picowind-components';
                $relative_path = '@picowind-components/' . $file->getRelativePathname();
            } else {
                continue; // Skip files not in supported directories
            }

            $entries[] = [
                'name' => $file->getFilename(),
                'relative_path' => $relative_path,
                'content' => $file->getContents(),
                'handler' => $handler,
                'signature' => wp_create_nonce(sprintf('%s:%s', $handler, $file->getRelativePathname())),
                // 'readonly' => true,
                'path_on_disk' => $file->getPathname(),
            ];
        }

        return array_merge($sfs_entries, $entries);
    }

    #[Hook('a!windpress/core/volume:save_entries.entry.picowind-root', 'action')]
    public function sfs_handler_save_root(array $entry): void
    {
        $this->sfs_handler_save($entry);
    }

    #[Hook('a!windpress/core/volume:save_entries.entry.picowind-views', 'action')]
    public function sfs_handler_save_views(array $entry): void
    {
        $this->sfs_handler_save($entry);
    }

    #[Hook('a!windpress/core/volume:save_entries.entry.picowind-blocks', 'action')]
    public function sfs_handler_save_blocks(array $entry): void
    {
        $this->sfs_handler_save($entry);
    }

    #[Hook('a!windpress/core/volume:save_entries.entry.picowind-components', 'action')]
    public function sfs_handler_save_components(array $entry): void
    {
        $this->sfs_handler_save($entry);
    }

    public function sfs_handler_save(array $entry): void
    {
        if (! isset($entry['signature']) || ! isset($entry['handler'])) {
            return;
        }

        $template_dir = get_template_directory();
        $handler = $entry['handler'];

        // Determine directory and relative path based on handler
        if ('picowind-root' === $handler) {
            $data_dir = $template_dir . '/assets/styles';
            $_relativePath = substr((string) $entry['relative_path'], strlen('@picowind/'));
        } elseif ('picowind-views' === $handler) {
            $data_dir = $template_dir . '/views';
            $_relativePath = substr((string) $entry['relative_path'], strlen('@picowind-views/'));
        } elseif ('picowind-blocks' === $handler) {
            $data_dir = $template_dir . '/blocks';
            $_relativePath = substr((string) $entry['relative_path'], strlen('@picowind-blocks/'));
        } elseif ('picowind-components' === $handler) {
            $data_dir = $template_dir . '/components';
            $_relativePath = substr((string) $entry['relative_path'], strlen('@picowind-components/'));
        } else {
            return; // Unknown handler
        }

        // verify the signature
        if (! wp_verify_nonce($entry['signature'], sprintf('%s:%s', $handler, $_relativePath))) {
            return;
        }

        try {
            // if the content is empty, delete the file.
            if (! isset($entry['content']) || '' === $entry['content']) {
                Common::delete_file($data_dir . '/' . $_relativePath);
            } else {
                Common::save_file($entry['content'], $data_dir . '/' . $_relativePath);
            }
        } catch (Throwable $throwable) {
            if (WP_DEBUG_LOG) {
                error_log($throwable->__toString());
            }
        }
    }
}
