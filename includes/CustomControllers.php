<?php
namespace HelloWP\FluentExtendTriggers\Includes;

if (!defined('ABSPATH')) {
    exit;
}

class CustomControllers
{
    public function __construct()
    {
        add_filter('fluentcrm_ajax_options_jfb_forms', [$this, 'get_jfb_forms'], 10, 3);
        add_filter('fluentcrm_ajax_options_post_statuses', [$this, 'get_post_statuses'], 10, 3);
        add_filter('fluentcrm_ajax_options_post_types', [$this, 'get_post_types'], 10, 3);
    }

    public function get_jfb_forms($options, $search, $includedIds)
    {
        $forms = get_posts([
            'post_type' => 'jet-form-builder',
            'numberposts' => -1,
            's' => $search
        ]);

        foreach ($forms as $form) {
            $options[] = [
                'id'    => $form->ID,
                'title' => $form->post_title
            ];
        }

        return $options;
    }

    public function get_post_statuses($options, $search, $includedIds)
    {
        $default_statuses = ['publish', 'pending', 'draft', 'private', 'trash'];

        $statuses = get_post_stati([], 'objects');

        foreach ($statuses as $status) {
            if (in_array($status->name, $default_statuses)) {
                $options[] = [
                    'id'    => $status->name,
                    'title' => $status->label
                ];
            }
        }

        return $options;
    }

    public function get_post_types($options, $search, $includedIds)
    {
        $post_types = get_post_types(['public' => true], 'objects');

        foreach ($post_types as $post_type) {
            if (strpos($post_type->name, $search) !== false || empty($search)) {
                $options[] = [
                    'id'    => $post_type->name,
                    'title' => $post_type->label
                ];
            }
        }

        return $options;
    }
}
