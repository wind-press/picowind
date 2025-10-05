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
use Picowind\Utils\Theme as UtilsTheme;
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

        $finder = new Finder();

        $themeDirs = [UtilsTheme::current_dir()];
        if (UtilsTheme::is_child_theme()) {
            $themeDirs[] = UtilsTheme::parent_dir();
        }

        // Scan the theme directory according to the file extensions
        foreach ($file_extensions as $file_extension) {
            $finder
                ->files()
                ->in($themeDirs)
                ->notPath('vendor')
                ->name('*.' . $file_extension);
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
        $finder = new Finder();
        $entries = [];
        $existing_dirs = [];

        $asset_paths = [
            UtilsTheme::current_dir() . '/public/styles',
        ];

        if (UtilsTheme::is_child_theme()) {
            $asset_paths[] = UtilsTheme::parent_dir() . '/public/styles';
        }

        foreach ($asset_paths as $asset_path) {
            if (file_exists($asset_path)) {
                $existing_dirs[] = $asset_path;
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

        do_action('a!picowind/supports/windpress:get_entries.finder', $finder);

        foreach ($finder as $file) {
            if (! is_readable($file->getPathname())) {
                continue;
            }

            // Detect directory based on file path
            $file_path = $file->getPathname();

            if (UtilsTheme::is_child_theme() && str_starts_with($file_path, UtilsTheme::current_dir())) {
                $handler = 'picowind-child';
                $relative_path = '@picowind/' . $file->getRelativePathname();
            } elseif (str_starts_with($file_path, UtilsTheme::is_child_theme() ? UtilsTheme::parent_dir() : UtilsTheme::current_dir())) {
                $handler = 'picowind-parent';
                $relative_path = '@picowind/' . $file->getRelativePathname();
            } else {
                continue;
            }

            // Child theme files take precedence over parent theme files.
            if ($handler === 'picowind-parent' && isset($entries[$relative_path])) {
                continue;
            }

            $entries[$relative_path] = [
                'name' => $file->getFilename(),
                'relative_path' => $relative_path,
                'content' => $file->getContents(),
                'handler' => $handler,
                'signature' => wp_create_nonce(sprintf('%s:%s', $handler, $file->getRelativePathname())),
                // 'readonly' => true,
                'path_on_disk' => $file->getPathname(),
            ];
        }

        return array_merge($sfs_entries, array_values($entries));
    }

    #[Hook('a!windpress/core/volume:save_entries.entry.picowind-child', 'action')]
    #[Hook('a!windpress/core/volume:save_entries.entry.picowind-parent', 'action')]
    public function sfs_handler_save(array $entry): void
    {
        if (! isset($entry['signature']) || ! isset($entry['handler'])) {
            return;
        }

        $handler = $entry['handler'];
        if ('picowind-child' === $handler) {
            if (! UtilsTheme::is_child_theme()) {
                return; // No child theme to save to
            }
            $data_dir = UtilsTheme::current_dir() . '/public/styles';
            $_relativePath = substr((string) $entry['relative_path'], strlen('@picowind/'));
        } elseif ('picowind-parent' === $handler) {
            $data_dir = UtilsTheme::is_child_theme()
                ? UtilsTheme::parent_dir() . '/public/styles'
                : UtilsTheme::current_dir() . '/public/styles';
            $_relativePath = substr((string) $entry['relative_path'], strlen('@picowind/'));
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
