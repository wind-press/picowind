<?php

declare(strict_types=1);

namespace Picowind;

use Picowind\Core\Discovery\Attributes\Hook;
use Picowind\Core\Discovery\Attributes\Service;
use Picowind\Utils\Config;

#[Service]
class Comment
{
    /**
     * Add daisyUI classes to comment form fields
     */
    #[Hook('comment_form_default_fields', type: 'filter')]
    public function add_daisyui_comment_form_fields(array $fields): array
    {
        if (! Config::get('features.daisyui', false)) {
            return $fields;
        }

        $replace = array(
            '<p class="' => '<div class="form-control mb-4 ',
            '<input' => '<input class="input input-bordered w-full focus:input-primary" ',
            '<label' => '<label class="label" ',
            '</label>' => '<span class="label-text font-semibold"></span></label>',
            '</p>' => '</div>',
        );
        if (isset($fields['author'])) {
            $fields['author'] = strtr($fields['author'], $replace);
        }
        if (isset($fields['email'])) {
            $fields['email'] = strtr($fields['email'], $replace);
        }
        if (isset($fields['url'])) {
            $fields['url'] = strtr($fields['url'], $replace);
        }
        // Checkbox for cookies consent
        $replace_checkbox = array(
            '<p class="' => '<div class="form-control mb-4 ',
            '<input' => '<input class="checkbox checkbox-primary" ',
            '<label' => '<label class="label cursor-pointer justify-start gap-2" ',
            '</p>' => '</div>',
        );
        if (isset($fields['cookies'])) {
            $fields['cookies'] = strtr($fields['cookies'], $replace_checkbox);
        }
        return $fields;
    }

    /**
     * Add daisyUI classes to comment form submit button and comment field
     */
    #[Hook('comment_form_defaults', type: 'filter')]
    public function add_daisyui_comment_form(array $args): array
    {
        if (! Config::get('features.daisyui', false)) {
            return $args;
        }

        $replace = array(
            '<p class="' => '<div class="form-control mb-4 ',
            '<textarea' => '<textarea class="textarea textarea-bordered w-full focus:textarea-primary" rows="6" ',
            '<label' => '<label class="label" ',
            '</label>' => '<span class="label-text font-semibold"></span></label>',
            '</p>' => '</div>',
        );
        if (isset($args['comment_field'])) {
            $args['comment_field'] = strtr($args['comment_field'], $replace);
        }
        if (isset($args['class_submit'])) {
            $args['class_submit'] = 'btn btn-primary btn-lg gap-2';
        }
        // Add wrapper classes for the submit button container
        if (isset($args['submit_button'])) {
            $args['submit_button'] = '<button name="%1$s" type="submit" id="%2$s" class="%3$s">%4$s</button>';
        }
        if (isset($args['submit_field'])) {
            $args['submit_field'] = '<div class="form-submit flex items-center gap-3 mt-6">%1$s %2$s</div>';
        }
        return $args;
    }

    /**
     * Add daisyUI classes to comment reply link
     */
    #[Hook('comment_reply_link_args', type: 'filter')]
    public function add_daisyui_comment_reply_link(array $args): array
    {
        if (! Config::get('features.daisyui', false)) {
            return $args;
        }

        $args['class'] = 'btn btn-sm btn-primary btn-outline gap-2';
        return $args;
    }

    /**
     * Add daisyUI classes to comment navigation links
     */
    #[Hook('previous_comments_link_attributes', type: 'filter')]
    #[Hook('next_comments_link_attributes', type: 'filter')]
    public function add_daisyui_comment_navigation_link(): string
    {
        if (! Config::get('features.daisyui', false)) {
            return '';
        }

        return 'class="btn btn-outline"';
    }

    /**
     * Add classes to comment list
     */
    #[Hook('comment_class', type: 'filter')]
    public function add_daisyui_comment_class(array $classes): array
    {
        if (! Config::get('features.daisyui', false)) {
            return $classes;
        }

        // Remove default WordPress classes
        $classes = array_diff($classes, ['comment', 'even', 'odd', 'thread-even', 'thread-odd', 'depth-1']);
        return $classes;
    }

    /**
     * Modify edit comment link
     */
    #[Hook('edit_comment_link', type: 'filter')]
    public function modify_edit_comment_link(string $link): string
    {
        if (! Config::get('features.daisyui', false)) {
            return $link;
        }

        return str_replace('comment-edit-link', 'btn btn-xs btn-ghost', $link);
    }
}
