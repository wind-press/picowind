<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind\Supports;

use Picowind\Core\Template;
use Picowind\Utils\Theme as UtilsTheme;
use Timber\Timber;

class AdvancedCustomFields
{
    public function __construct()
    {
        add_filter('block_type_metadata', [$this, 'metadata'], 2);
    }

    public function metadata(array $metadata): array
    {
        if (! isset($metadata['file'])) {
            return $metadata;
        }

        // Check if the block's file is within any of the blocks directories
        if (strpos($metadata['file'], UtilsTheme::current_dir() . '/blocks') === false) {
            if (UtilsTheme::is_child_theme() && strpos($metadata['file'], UtilsTheme::parent_dir() . '/blocks') === false) {
                return $metadata;
            }
        }

        if (strpos($metadata['name'], 'acf/') === false) {
            return $metadata;
        }

        if (! isset($metadata['acf'])) {
            $metadata['acf'] = [
                'picowind' => true,
            ];
        }

        return $metadata;
    }

    public static function block_render_callback(array $block, string $content = '', bool $is_preview = false, int $post_id = 0): void
    {
        $context = Timber::context();
        $context['post'] = Timber::get_post();
        $context['block'] = $block;
        $context['fields'] = get_fields();
        $context['content'] = $content;
        $context['is_preview'] = $is_preview;

        Template::render($block['render_engine'], $block['path'] . '/index.?', $context);
    }
}
