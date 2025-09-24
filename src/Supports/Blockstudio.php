<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind\Supports;

use Blockstudio\Build;
use Picowind\Core\Template as CoreTemplate;

class Blockstudio
{
    public function __construct()
    {
        add_filter('blockstudio/settings/users/roles', [$this, 'editor_access']);
        add_filter('blockstudio/settings/tailwind/enabled', static fn () => false);
        add_filter('picowind/blocks/register:should-register', [$this, 'should_register'], 10, 3);
        add_action('picowind/blocks/register:before', [$this, 'register_blocks'], 10, 2);
        add_filter('blockstudio/blocks/render', [$this, 'block_render'], 10, 4);
    }

    public function editor_access($roles): array
    {
        $roles[] = 'administrator';
        return $roles;
    }

    public function should_register(bool $should_register, array $block_json, string $dir_path): bool
    {
        if (isset($block_json['blockstudio']) && ($block_json['blockstudio'] !== true || $block_json['blockstudio'] !== 1)) {
            return false;
        }

        return $should_register;
    }

    public function register_blocks(array $block_json, string $dir_path): void
    {
        if (! class_exists(Build::class)) {
            return;
        }

        if (! isset($block_json['blockstudio']) || $block_json['blockstudio'] !== true && $block_json['blockstudio'] !== 1) {
            return;
        }

        Build::init([
            'dir' => $dir_path,
        ]);
    }

    public function block_render($value, $block, $isEditor, $isPreview)
    {
        $blockPath = $block->path;

        // only render blocks from the /blocks directory
        $in_blocks_dir = false;
        foreach (CoreTemplate::get_instance()->template_dirs as $dir_path) {
            if (strpos($blockPath, $dir_path) !== false) {
                $in_blocks_dir = true;
                break;
            }
        }
        if (! $in_blocks_dir) {
            return $value;
        }

        // if not a blade or twig file, return the original value
        if (! str_ends_with($blockPath, '.blade.php') && ! str_ends_with($blockPath, '.twig')) {
            return $value;
        }

        $data = $block->blockstudio['data'];

        $rendered = CoreTemplate::render(
            $blockPath,
            [
                'a' => $data['attributes'],
                'attributes' => $data['attributes'],
                'b' => $data['block'],
                'block' => $data['block'],
                'c' => $data['context'],
                'context' => $data['context'],
                'isPreview' => $isPreview,
                'isEditor' => $isEditor,
            ],
            null,
            false,
        );

        return $rendered;
    }
}
