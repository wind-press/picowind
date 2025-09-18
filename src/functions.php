<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind;

use Timber\Timber;

function acf_block_render_callback(array $block, string $content = '', bool $is_preview = false, int $post_id = 0): void
{
    $context = Timber::context();
    $context['post'] = Timber::get_post();
    $context['block'] = $block;
    $context['fields'] = get_fields();
    $context['content'] = $content;
    $context['is_preview'] = $is_preview;

    $slug = explode('/', $block['name'])[1];
    $template = 'blocks/' . $slug . '/index.twig';

    Timber::render($template, $context);
}

/**
 * Get an instance of Symfony Finder, if available.
 *
 * @return null|\WindPressDeps\Symfony\Component\Finder\Finder|\Symfony\Component\Finder\Finder
 * @mago-expect lint:return-type
 */
function get_symfony_finder()
{
    if (class_exists('\WindPressDeps\Symfony\Component\Finder\Finder')) {
        return new \WindPressDeps\Symfony\Component\Finder\Finder();
    } elseif (class_exists('\Symfony\Component\Finder\Finder')) {
        return new \Symfony\Component\Finder\Finder();
    } else {
        return null;
    }
}
