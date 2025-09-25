<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind;

use Timber\Timber;

$context = context();
$timber_post = Timber::get_post();
$context['post'] = $timber_post;

if (post_password_required($timber_post->ID)) {
    render('single-password.twig', $context, 'twig');
} else {
    render(['single-' . $timber_post->ID . '.twig', 'single-' . $timber_post->post_type . '.twig', 'single-' . $timber_post->slug . '.twig', 'single.twig'], $context, 'twig');
}
