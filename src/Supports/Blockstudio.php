<?php

/**
 * @package WordPress
 * @subpackage Picowind
 * @since Picowind 1.0.0
 */

namespace Picowind\Supports;

class Blockstudio
{
    public function __construct()
    {
        add_action('blockstudio/settings/users/roles', [$this, 'editor_access']);
        add_action('blockstudio/settings/tailwind/enabled', static fn () => false);
    }

    public function editor_access($roles): array
    {
        $roles[] = 'administrator';
        return $roles;
    }
}
