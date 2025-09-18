<?php
/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind;

use Timber\Timber;

$context = Timber::context();

$timber_post = Timber::get_post();
$context['post'] = $timber_post;
Timber::render(['page-' . $timber_post->post_name . '.twig', 'page.twig'], $context);
