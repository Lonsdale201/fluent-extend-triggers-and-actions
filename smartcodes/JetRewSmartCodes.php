<?php

namespace HelloWP\FluentExtendTriggers\SmartCodes;

class JetRewSmartCodes
{
    public function __construct()
    {
        add_action('fluent_crm/after_init', [$this, 'registerSmartCodes']);
    }

    public function registerSmartCodes()
    {
        $key = 'jet_review';
        $title = 'JetReview';
        $shortCodes = [
            'title' => 'Latest Review Title',
            'source' => 'Latest Review Source',
            'content' => 'Latest Review Content',
            'approved_title' => 'Latest Approved Review Title',
            'approved_source' => 'Latest Approved Review Source',
            'approved_content' => 'Latest Approved Review Content'
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

        if (strpos($valueKey, 'approved_') === 0) {
            $latestReview = $this->getLatestApprovedReviewByUser($userId);
            $valueKey = str_replace('approved_', '', $valueKey);
        } else {
            $latestReview = $this->getLatestReviewByUser($userId);
        }

        if (!$latestReview) {
            return $defaultValue;
        }

        switch ($valueKey) {
            case 'title':
                return $latestReview->title;
            case 'source':
                $postTitle = get_the_title($latestReview->post_id);
                $postUrl = get_permalink($latestReview->post_id);
                return '<a href="' . $postUrl . '">' . $postTitle . '</a>';
            case 'content':
                return $latestReview->content;
            default:
                return $defaultValue;
        }
    }

    private function getLatestReviewByUser($userId)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jet_reviews';

        $query = $wpdb->prepare("
            SELECT * FROM $table_name 
            WHERE author = %d 
            ORDER BY date DESC 
            LIMIT 1
        ", $userId);

        return $wpdb->get_row($query);
    }

    private function getLatestApprovedReviewByUser($userId)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jet_reviews';

        $query = $wpdb->prepare("
            SELECT * FROM $table_name 
            WHERE author = %d AND approved = 1
            ORDER BY date DESC 
            LIMIT 1
        ", $userId);

        return $wpdb->get_row($query);
    }
}
