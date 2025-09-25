<?php

declare(strict_types=1);

/**
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind;

use Timber\Timber;

global $wp_query;

$context = context();
$context['posts'] = Timber::get_posts();
if (isset($wp_query->query_vars['author'])) {
    $author = Timber::get_user($wp_query->query_vars['author']);
    $context['author'] = $author;
    $context['title'] = 'Author Archives: ' . $author->name();
}
render(['author.twig', 'archive.twig'], $context, 'twig');
