<?php

namespace HelloWP\FluentExtendTriggers\SmartCodes;

class PostSmartCodes
{
    public function __construct()
    {
        add_action('fluent_crm/after_init', [$this, 'registerSmartCodes']);
    }

    public function registerSmartCodes()
    {
        $key = 'latest_post';
        $title = 'Post';
        $shortCodes = [
            'title' => 'Post Title',
            'title_link' => 'Post Title Link',
            'published_date' => 'Published Date',
        ];
        $callback = [$this, 'handleSmartCode'];

        FluentCrmApi('extender')->addSmartCode($key, $title, $shortCodes, $callback);
    }

    public function handleSmartCode($code, $valueKey, $defaultValue, $subscriber)
    {
        $userId = $subscriber->getWpUserId();
        if (!$userId) {
            return $defaultValue;
        }

        $latestPost = $this->getLatestPostByUser($userId);
        if (!$latestPost) {
            return $defaultValue;
        }

        switch ($valueKey) {
            case 'title':
                return $latestPost->post_title;
            case 'title_link':
                return '<a href="' . get_permalink($latestPost->ID) . '">' . $latestPost->post_title . '</a>';
            case 'published_date':
                return date('Y-m-d H:i:s', strtotime($latestPost->post_date));
            default:
                return $defaultValue;
        }
    }

    private function getLatestPostByUser($userId)
    {
        $args = [
            'author' => $userId,
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => 'publish',
            'post_type' => 'any'
        ];

        $query = new \WP_Query($args);
        if ($query->have_posts()) {
            return $query->posts[0];
        }

        return null;
    }
}
