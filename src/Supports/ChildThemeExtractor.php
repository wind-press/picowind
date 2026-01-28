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
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use WP_Theme;

#[Service]
class ChildThemeExtractor
{
    private const CHILD_THEME_BASE_NAME = 'picowind-child';

    private string $sourcePath;

    private Filesystem $filesystem;

    public function __construct()
    {
        $this->sourcePath = get_template_directory() . '/child-theme/base';
        $this->filesystem = new Filesystem();
    }

    #[Hook(name: 'after_switch_theme', priority: 1_000_001, accepted_args: 2)]
    public function extract_on_theme_activation(string $new_name, WP_Theme $new_theme): void
    {
        if (! $this->is_parent_theme_activated($new_theme)) {
            return;
        }

        $this->extract();
    }

    public function extract(): void
    {
        if (! $this->filesystem->exists($this->sourcePath)) {
            throw new RuntimeException('Source child theme directory not found: ' . $this->sourcePath);
        }

        $childThemeName = $this->find_or_create_child_theme();

        switch_theme($childThemeName);

        do_action('picowind_child_theme_extracted', $childThemeName);
    }

    private function find_or_create_child_theme(): string
    {
        $themeName = self::CHILD_THEME_BASE_NAME;
        $counter = 0;

        while ($this->filesystem->exists($this->get_theme_path($themeName))) {
            if ($this->is_picowind_child_theme($themeName)) {
                return $themeName;
            }

            $counter++;
            $themeName = self::CHILD_THEME_BASE_NAME . '-' . $counter;
        }

        $this->filesystem->mirror($this->sourcePath, $this->get_theme_path($themeName));

        return $themeName;
    }

    private function is_parent_theme_activated(WP_Theme $theme): bool
    {
        return $theme->get_template() === 'picowind' && $theme->get_stylesheet() === 'picowind';
    }

    private function is_picowind_child_theme(string $themeSlug): bool
    {
        $theme = wp_get_theme($themeSlug);

        if (! $theme->exists()) {
            return false;
        }

        return $theme->get_template() === 'picowind' && $theme->get_stylesheet() !== 'picowind';
    }

    private function get_theme_path(string $themeName): string
    {
        return get_theme_root() . '/' . $themeName;
    }
}
