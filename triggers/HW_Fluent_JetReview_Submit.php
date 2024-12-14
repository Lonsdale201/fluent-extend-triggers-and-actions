<?php

namespace HelloWP\FluentExtendTriggers\Triggers;

use FluentCrm\App\Services\Funnel\BaseTrigger;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\Framework\Support\Arr;

class HW_Fluent_JetReview_Submit extends BaseTrigger
{
    public function __construct()
    {
        $this->triggerName = 'jet_review_submit';
        $this->priority = 22;
        $this->actionArgNum = 1;
        parent::__construct();
    }

    public function getTrigger()
    {
        return [
            'category'    => __('JetReviews', 'hw-fluent-extendtriggers'),
            'label'       => __('JetReview Event', 'hw-fluent-extendtriggers'),
            'description' => __('This trigger fires when a JetReview event occurs.', 'hw-fluent-extendtriggers'),
            'icon'        => 'fc-icon-trigger',
        ];
    }

    public function getFunnelSettingsDefaults()
    {
        return [
            'subscription_status' => 'subscribed',
            'trigger_event' => 'submitted'
        ];
    }

    public function getSettingsFields($funnel)
    {
        return [
            'title'     => __('JetReview Event', 'hw-fluent-extendtriggers'),
            'sub_title' => __('This will start when a JetReview event occurs', 'hw-fluent-extendtriggers'),
            'fields'    => [
                'subscription_status' => [
                    'type'        => 'option_selectors',
                    'option_key'  => 'editable_statuses',
                    'is_multiple' => false,
                    'label'       => __('Subscription Status', 'fluentcampaign-pro'),
                    'placeholder' => __('Select Status', 'fluentcampaign-pro')
                ],
                'subscription_status_info' => [
                    'type'       => 'html',
                    'info'       => '<b>' . __('An Automated double-optin email will be sent for new subscribers', 'fluentcampaign-pro') . '</b>',
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
            'run_multiple' => 'no',
            'trigger_event' => 'approved'
        ];
    }

    public function getConditionFields($funnel)
    {
        return [
            'run_multiple' => [
                'type'        => 'yes_no_check',
                'label'       => '',
                'check_label' => __('Restart the Automation Multiple times for a contact for this event. (Only enable if you want to restart automation for the same contact)', 'fluentcampaign-pro'),
                'inline_help' => __('If you enable, then it will restart the automation for a contact if the contact already in the automation. Otherwise, It will just skip if already exist', 'fluentcampaign-pro')
            ],
            'trigger_event' => [
                'type'        => 'select',
                'label'       => __('Trigger Event', 'hw-fluent-extendtriggers'),
                'options'     => [
                    [
                        'id'    => 'submitted',
                        'title' => __('Submitted', 'hw-fluent-extendtriggers')
                    ],
                    [
                        'id'    => 'approved',
                        'title' => __('Approved', 'hw-fluent-extendtriggers')
                    ]
                ],
                'default'     => 'approved'
            ]
        ];
    }

    public function handle($funnel, $originalArgs)
    {
        if (count($originalArgs) < 1 || !isset($originalArgs[0]['review_event'])) {
            return false;
        }

        $review = $originalArgs[0];
        $userId = isset($review['author_id']) ? $review['author_id'] : null;
        $email = isset($review['author_mail']) ? $review['author_mail'] : null;

        if (!$userId) {
            return false;
        }

        if (!$email && $userId) {
            $user = get_user_by('ID', $userId);
            if ($user && isset($user->user_email)) {
                $email = $user->user_email;
            } else {
                return false;
            }
        }

        $subscriberData = FunnelHelper::prepareUserData($userId);
        $subscriberData['email'] = $email;
        $subscriberData['review_event'] = $review['review_event'];

        if (!is_email($subscriberData['email'])) {
            return;
        }

        if (!$this->isProcessable($funnel, $subscriberData)) {
            return false;
        }

        $subscriberData = wp_parse_args($subscriberData, $funnel->settings);
        $subscriberData['status'] = (!empty($subscriberData['subscription_status'])) ? $subscriberData['subscription_status'] : 'subscribed';
        unset($subscriberData['subscription_status']);

        (new FunnelProcessor())->startFunnelSequence($funnel, $subscriberData, [
            'source_trigger_name' => $this->triggerName,
            'source_ref_id' => $review['id']
        ]);
    }

    private function isProcessable($funnel, $subscriberData)
    {
        $conditions = $funnel->conditions;

        if (isset($conditions['trigger_event']) && $conditions['trigger_event'] !== $subscriberData['review_event']) {
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

// Hook into the JetReviews submit-review action
add_action('jet-reviews/endpoints/reviews/submit-review', function($args, $insert_data) {
    $reviewData = [
        'review_event' => 'submitted',
        'id' => $insert_data['insert_id'],
        'author_id' => isset($args['author_id']) ? $args['author_id'] : null,
        'author_mail' => isset($args['author_mail']) ? $args['author_mail'] : null
    ];

    do_action('jet_review_submit', $reviewData);
}, 10, 2);

// Hook into the JetReviews toggle-review-approve action
add_action('jet-reviews/endpoints/reviews/toggle-review-approve', function($args) {
    $review_id = isset($args['itemsList'][0]['id']) ? $args['itemsList'][0]['id'] : null;
    if ($review_id) {
        $review = get_review_data($review_id);
        $author_id = isset($review->author) ? $review->author : null;
        $author_mail = null;

        if ($author_id) {
            $user = get_user_by('ID', $author_id);
            if ($user && isset($user->user_email)) {
                $author_mail = $user->user_email;
            }
        }

        if (isset($args['itemsList'][0]['approved']) && $args['itemsList'][0]['approved'] == '1') {
            $review_event = 'approved';
        } else {
            $review_event = 'unapproved';
        }

        $reviewData = [
            'review_event' => $review_event,
            'id' => $review_id,
            'author_id' => $author_id,
            'author_mail' => $author_mail
        ];

        do_action('jet_review_submit', $reviewData);
    }
}, 10, 1);

function get_review_data($review_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'jet_reviews';
    $review = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $review_id));
    return $review;
}
