<?php
namespace HelloWP\FluentExtendTriggers\Triggers;

use FluentCrm\App\Services\Funnel\BaseTrigger;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\Framework\Support\Arr;

class HW_Fluent_JFB_Post_Insert extends BaseTrigger
{
    public function __construct()
    {
        $this->triggerName = 'jetformbuilder_post_insert';
        $this->priority = 22;
        $this->actionArgNum = 1; 
        parent::__construct();
    }

    public function getTrigger()
    {
        return [
            'category'    => __('JetFormBuilder', 'hw-fluent-extendtriggers'),
            'label'       => __('JetFormBuilder Post Insert', 'hw-fluent-extendtriggers'),
            'description' => __('This trigger fires when a JetFormBuilder form successfully inserts a post.', 'hw-fluent-extendtriggers'),
            'icon'        => 'fc-icon-trigger',
        ];
    }

    public function getFunnelSettingsDefaults()
    {
        return [
            'subscription_status' => 'subscribed',
        ];
    }

    public function getSettingsFields($funnel)
    {
        return [
            'title'     => __('JetFormBuilder Post Insert', 'hw-fluent-extendtriggers'),
            'sub_title' => __('This will start when a JetFormBuilder form successfully inserts a post', 'hw-fluent-extendtriggers'),
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
            'form_ids'   => [],
            'post_statuses' => [],
            'run_multiple' => 'no'
        ];
    }

    public function getConditionFields($funnel)
    {
        return [
            'form_ids' => [
                'type'        => 'rest_selector',
                'option_key'  => 'jfb_forms',
                'is_multiple' => true,
                'label'       => __('Select Forms', 'hw-fluent-extendtriggers'),
                'help'        => __('Select for which forms this automation will run', 'hw-fluent-extendtriggers'),
                'inline_help' => __('Keep it blank to run on any form submission', 'hw-fluent-extendtriggers')
            ],
            'post_statuses' => [
                'type'        => 'rest_selector',
                'option_key'  => 'post_statuses',
                'is_multiple' => true,
                'label'       => __('Select Post Statuses', 'hw-fluent-extendtriggers'),
                'help'        => __('Select for which post statuses this automation will run', 'hw-fluent-extendtriggers'),
                'inline_help' => __('Keep it blank to run on any post status', 'hw-fluent-extendtriggers')
            ],
            'run_multiple' => [
                'type'        => 'yes_no_check',
                'label'       => '',
                'check_label' => __('Restart the Automation Multiple times for a contact for this event. (Only enable if you want to restart automation for the same contact)', 'fluentcampaign-pro'),
                'inline_help' => __('If you enable, then it will restart the automation for a contact if the contact already in the automation. Otherwise, It will just skip if already exist', 'fluentcampaign-pro')
            ]
        ];
    }

    public function handle($funnel, $originalArgs)
    {
        if (!isset($originalArgs[0])) {
            return false;
        }

        $action = $originalArgs[0];
        $handler = jet_fb_action_handler(); 

        if (!is_object($handler) || !method_exists($handler, 'get_inserted_post_id')) {
            return false;
        }

        $formId = $handler->form_id ?? null;
        $postId = $handler->get_inserted_post_id($action->_id);
        $postStatus = get_post_status($postId);
        $userId = get_current_user_id();

        if (!$postId) {
            return false;
        }

        $subscriberData = FunnelHelper::prepareUserData($userId);

        if (!is_email($subscriberData['email'])) {
            return;
        }

        if (!$this->isProcessable($funnel, $formId, $subscriberData, $postStatus)) {
            return false;
        }

        $subscriberData = wp_parse_args($subscriberData, $funnel->settings);
        $subscriberData['status'] = (!empty($subscriberData['subscription_status'])) ? $subscriberData['subscription_status'] : 'subscribed';
        unset($subscriberData['subscription_status']);

        (new FunnelProcessor())->startFunnelSequence($funnel, $subscriberData, [
            'source_trigger_name' => $this->triggerName,
            'source_ref_id' => $postId
        ]);
    }

    private function isProcessable($funnel, $formId, $subscriberData, $postStatus)
    {
        $conditions = $funnel->conditions;

        if (!empty($conditions['form_ids']) && !in_array($formId, $conditions['form_ids'])) {
            return false;
        }

        if (!empty($conditions['post_statuses']) && !in_array($postStatus, $conditions['post_statuses'])) {
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

add_action('jet-form-builder/action/after-post-insert', function($action) {
    do_action('jetformbuilder_post_insert', $action);
}, 10, 1);
