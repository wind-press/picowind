<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind;

use Timber\Timber;

$templates = ['search.twig', 'archive.twig', 'index.twig'];

$context = context();
$context['title'] = 'Search results for ' . get_search_query();
$context['posts'] = Timber::get_posts();

render($templates, $context, 'twig');
