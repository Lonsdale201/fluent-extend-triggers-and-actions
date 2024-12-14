<?php

namespace HelloWP\FluentExtendTriggers\Triggers;

use FluentCrm\App\Services\Funnel\BaseTrigger;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\App\Models\Subscriber;

class ReviewAddedTrigger extends BaseTrigger
{
    public function __construct()
    {
        $this->triggerName = 'woocommerce_review_added';
        $this->priority = 22;
        $this->actionArgNum = 3;
        parent::__construct();
    }

    public function getTrigger()
    {
        return [
            'ribbon'      => 'Extend plugin',
            'category'    => __('WooCommerce', 'hw-fluent-extendtriggers'),
            'label'       => __('Review Added', 'hw-fluent-extendtriggers'),
            'description' => __('This funnel will start once a review is added', 'hw-fluent-extendtriggers'),
            'icon'        => 'fc-icon-woo_new_order',
        ];
    }

    public function getFunnelSettingsDefaults()
    {
        return [
            'subscription_status' => 'subscribed'
        ];
    }

    public function getSettingsFields($funnel)
    {
        return [
            'title'     => __('Review Added', 'hw-fluent-extendtriggers'),
            'sub_title' => __('This Funnel will start once a review is added', 'hw-fluent-extendtriggers'),
            'fields'    => [
                'subscription_status' => [
                    'type'        => 'option_selectors',
                    'option_key'  => 'editable_statuses',
                    'is_multiple' => false,
                    'label'       => __('Subscription Status', 'hw-fluent-extendtriggers'),
                    'placeholder' => __('Select Status', 'hw-fluent-extendtriggers')
                ],
                'subscription_status_info' => [
                    'type'       => 'html',
                    'info'       => '<b>' . __('An Automated double-optin email will be sent for new subscribers', 'hw-fluent-extendtriggers') . '</b>',
                    'dependency' => [
                        'depends_on' => 'subscription_status',
                        'operator'   => '=',
                        'value'      => 'pending'
                    ]
                ]
            ]
        ];
    }

    public function getFunnelConditionDefaults($funnel)
    {
        return [
            'product_ids' => [],
            'purchase_type' => 'any',
            'run_multiple' => 'no'
        ];
    }

    public function getConditionFields($funnel)
    {
        return [
            'product_ids' => [
                'type'        => 'rest_selector',
                'option_key'  => 'woo_products',
                'is_multiple' => true,
                'label'       => __('Target Products', 'hw-fluent-extendtriggers'),
                'help'        => __('Select for which products this automation will run', 'hw-fluent-extendtriggers'),
                'inline_help' => __('Keep it blank to run on any product review', 'hw-fluent-extendtriggers')
            ],
            'purchase_type' => [
                'type'        => 'radio',
                'label'       => __('Purchase Type', 'hw-fluent-extendtriggers'),
                'help'        => __('Select the purchase type', 'hw-fluent-extendtriggers'),
                'options'     => [
                    [
                        'id'    => 'any',
                        'title' => __('Any type', 'hw-fluent-extendtriggers')
                    ],
                    [
                        'id'    => 'purchased',
                        'title' => __('Only if purchased', 'hw-fluent-extendtriggers')
                    ]
                ],
                'inline_help' => __('Select "Any type" to trigger for any review, or "Only if purchased" to trigger only if the product was purchased', 'hw-fluent-extendtriggers')
            ],
            'run_multiple' => [
                'type'        => 'yes_no_check',
                'label'       => '',
                'check_label' => __('Restart the Automation Multiple times for a contact for this event. (Only enable if you want to restart automation for the same contact)', 'hw-fluent-extendtriggers'),
                'inline_help' => __('If you enable, then it will restart the automation for a contact if the contact is already in the automation. Otherwise, it will just skip if already exists', 'hw-fluent-extendtriggers')
            ]
        ];
    }

    public function handle($funnel, $originalArgs)
    {
        $commentId = $originalArgs[0];
        $comment = get_comment($commentId);
        $postId = $comment->comment_post_ID;
        $postType = get_post_type($postId);

        if ($postType !== 'product') {
            return false;
        }

        $userId = $comment->user_id;
        $subscriberData = $this->prepareSubscriberData($comment);

        // Ellenőrizzük, hogy létezik-e az email cím és érvényes-e
        if (empty($subscriberData['email']) || !is_email($subscriberData['email'])) {
            return false;
        }

        if (!$this->isProcessable($funnel, $subscriberData, $postId, $userId)) {
            return false;
        }

        $subscriberData = wp_parse_args($subscriberData, $funnel->settings);
        $subscriberData['status'] = (!empty($subscriberData['subscription_status'])) ? $subscriberData['subscription_status'] : 'subscribed';
        unset($subscriberData['subscription_status']);

        (new FunnelProcessor())->startFunnelSequence($funnel, $subscriberData, [
            'source_trigger_name' => $this->triggerName,
            'source_ref_id'       => $commentId
        ]);
    }

    private function prepareSubscriberData($comment)
    {
        $userId = $comment->user_id;
        $subscriberData = FunnelHelper::prepareUserData($userId);

        // Check if the user is already in the system
        $subscriber = FunnelHelper::getSubscriber($comment->comment_author_email);
        if ($subscriber) {
            // User is already in the system, do not update name
            $subscriberData['email'] = $comment->comment_author_email;
        } else {
            // New user, prepare full data
            if (empty($subscriberData['email'])) {
                $subscriberData['email'] = $comment->comment_author_email;
            }
            if ($userId == 0) {
                $subscriberData['first_name'] = $comment->comment_author;
                $subscriberData['last_name'] = '';
            }
        }

        return $subscriberData;
    }

    private function isProcessable($funnel, $subscriberData, $postId, $userId)
    {
        $conditions = (array) $funnel->conditions;
        $productIds = Arr::get($conditions, 'product_ids', []);
        $purchaseType = Arr::get($conditions, 'purchase_type', 'any');

        // Check if review matches product conditions
        if (!empty($productIds) && !in_array($postId, $productIds)) {
            return false;
        }

        // Check if review matches purchase type conditions
        if ($purchaseType === 'purchased' && !wc_customer_bought_product($subscriberData['email'], $userId, $postId)) {
            return false;
        }

        $subscriber = FunnelHelper::getSubscriber($subscriberData['email']);

        if ($subscriber && FunnelHelper::ifAlreadyInFunnel($funnel->id, $subscriber->id)) {
            $multipleRun = Arr::get($conditions, 'run_multiple') == 'yes';
            if ($multipleRun) {
                FunnelHelper::removeSubscribersFromFunnel($funnel->id, [$subscriber->id]);
            } else {
                return false;
            }
        }

        return true;
    }
}

add_action('comment_post', function($commentId, $commentApproved, $commentData) {
    if (get_post_type($commentData['comment_post_ID']) === 'product' && $commentData['comment_type'] === 'review') {
        do_action('woocommerce_review_added', $commentId, $commentApproved, $commentData);
    }
}, 10, 3);

