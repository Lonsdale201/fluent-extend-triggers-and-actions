<?php

namespace HelloWP\FluentExtendTriggers\Actions;

use FluentCrm\App\Services\Funnel\BaseAction;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\Framework\Support\Arr;

class HW_Fluent_Post_Actions extends BaseAction {
        
    public function __construct()
    {
        $this->actionName = 'update_posts_action';
        $this->priority = 20;
        parent::__construct();
    }
    
    public function getBlock()
    {
        return [
            'category'    => __('Posts', 'hw-fluent-extendtriggers'),
            'title'       => __('Update Posts', 'hw-fluent-extendtriggers'),
            'description' => __('Update post status for selected post types', 'hw-fluent-extendtriggers'),
            'icon'        => 'fc-icon-trigger',
        ];
    }
    
    public function getBlockFields()
    {
        return [
            'title'     => __('Update Post Status', 'hw-fluent-extendtriggers'),
            'sub_title' => __('Update the status of posts for the selected post types', 'hw-fluent-extendtriggers'),
            'fields'    => [
                'post_status' => [
                    'type'        => 'rest_selector',
                    'option_key'  => 'post_statuses',
                    'is_multiple' => false,
                    'clearable'   => true,
                    'label'       => __('Select Post Status', 'hw-fluent-extendtriggers'),
                    'placeholder' => __('Select Status', 'hw-fluent-extendtriggers')
                ],
                'post_types' => [
                    'type'        => 'rest_selector',
                    'option_key'  => 'post_types',
                    'is_multiple' => true,
                    'clearable'   => true,
                    'label'       => __('Select Post Types', 'hw-fluent-extendtriggers'),
                    'placeholder' => __('Select Post Types', 'hw-fluent-extendtriggers')
                ],
                'apply_to' => [
                    'type'    => 'radio_buttons',
                    'label'   => __('Apply to', 'hw-fluent-extendtriggers'),
                    'options' => [
                        [
                            'id'    => 'all_posts',
                            'title' => __('All Posts', 'hw-fluent-extendtriggers')
                        ],
                        [
                            'id'    => 'except_latest',
                            'title' => __('Except Latest', 'hw-fluent-extendtriggers')
                        ]
                    ],
                    'default' => 'all_posts'
                ]
            ]
        ];
    }
    
    public function handle($subscriber, $sequence, $funnelSubscriberId, $funnelMetric)
    {
        $settings = $sequence->settings;
        $userId = $subscriber->getWpUserId();
        
        $postStatus = Arr::get($settings, 'post_status');
        $postTypes = Arr::get($settings, 'post_types', []);
        $applyTo = Arr::get($settings, 'apply_to', 'all_posts');

        if (!$postStatus || empty($postTypes)) {
            $funnelMetric->notes = __('Funnel Skipped because no post status or post types found', 'hw-fluent-extendtriggers');
            $funnelMetric->save();
            FunnelHelper::changeFunnelSubSequenceStatus($funnelSubscriberId, $sequence->id, 'skipped');
            return false;
        }

        if (!$userId) {
            return false;
        }

        $args = [
            'author' => $userId,
            'post_type' => $postTypes,
            'posts_per_page' => -1,
            'post_status' => 'any',
            'orderby' => 'date',
            'order' => 'DESC'
        ];

        $query = new \WP_Query($args);
        if ($query->have_posts()) {
            $posts = $query->posts;
            if ($applyTo === 'except_latest' && count($posts) > 0) {
                array_shift($posts); 
            }
            foreach ($posts as $post) {
                if ($post->post_status === $postStatus) {
                    continue; 
                }
                wp_update_post([
                    'ID' => $post->ID,
                    'post_status' => $postStatus
                ]);
            }
        }

        $funnelMetric->notes = __('Posts updated successfully', 'hw-fluent-extendtriggers');
        $funnelMetric->save();
        FunnelHelper::changeFunnelSubSequenceStatus($funnelSubscriberId, $sequence->id, 'completed');
    }
}
