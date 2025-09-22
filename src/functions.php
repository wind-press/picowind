<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind;

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
