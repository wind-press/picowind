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
render(['page-' . $timber_post->post_name . '.twig', 'page.twig'], $context, 'twig');
