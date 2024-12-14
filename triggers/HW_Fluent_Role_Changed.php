<?php
namespace HelloWP\FluentExtendTriggers\Triggers;

use FluentCrm\App\Services\Funnel\BaseTrigger;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\Framework\Support\Arr;

class HW_Fluent_Role_Changed extends BaseTrigger
{
    public function __construct()
    {
        $this->triggerName = 'user_role_changed';
        $this->priority = 10;
        $this->actionArgNum = 1;
        parent::__construct();
    }

    public function getTrigger()
    {
        return [
            'ribbon'      => 'Extend plugin',
            'category'    => __('WordPress Triggers', 'hw-fluent-extendtriggers'),
            'label'       => __('User Role Changed', 'hw-fluent-extendtriggers'),
            'description' => __('This trigger fires when a user role is changed.', 'hw-fluent-extendtriggers'),
            'icon'        => 'fc-icon-trigger',
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
            'title'     => __('User Role Changed', 'hw-fluent-extendtriggers'),
            'sub_title' => __('This will start when a user role changes.', 'hw-fluent-extendtriggers'),
            'fields'    => [
                'subscription_status' => [
                    'type'        => 'option_selectors',
                    'option_key'  => 'editable_statuses',
                    'is_multiple' => false,
                    'label'       => __('Subscription Status', 'fluentcampaign-pro'),
                    'placeholder' => __('Select Status', 'fluentcampaign-pro')
                ],
            ]
        ];
    }

    public function getFunnelConditionDefaults($funnel)
    {
        return [
            'changed_from' => '',
            'changed_to'   => '',
            'run_multiple' => 'no'
        ];
    }

    public function getConditionFields($funnel)
    {
        return [
            'changed_from' => [
                'type'        => 'rest_selector',
                'option_key'  => 'available_roles',
                'is_multiple' => false,
                'label'       => __('Changed From Role', 'hw-fluent-extendtriggers'),
                'placeholder' => __('Select the original role', 'hw-fluent-extendtriggers'),
                'inline_help' => __('Select the role the user is changing from.', 'hw-fluent-extendtriggers'),
                'clearable'   => true
            ],
            'changed_to' => [
                'type'        => 'rest_selector',
                'option_key'  => 'available_roles',
                'is_multiple' => false,
                'label'       => __('Changed To Role', 'hw-fluent-extendtriggers'),
                'placeholder' => __('Select the new role', 'hw-fluent-extendtriggers'),
                'inline_help' => __('Select the role the user is changing to.', 'hw-fluent-extendtriggers'),
                'clearable'   => true
            ],
            'run_multiple' => [
                'type'        => 'yes_no_check',
                'label'       => '',
                'check_label' => __('Restart the Automation Multiple times for a contact for this event.', 'fluentcampaign-pro'),
                'inline_help' => __('If enabled, the automation restarts for the same contact if already in progress.', 'fluentcampaign-pro')
            ]
        ];
    }

    public function handle($funnel, $originalArgs)
    {
        $args = $originalArgs[0] ?? [];
        $user_id = $args[0] ?? null;
        $new_role = $args[1] ?? null;
        $old_roles = $args[2] ?? [];

        if (!$user_id || !$new_role) {
            return false;
        }

        $conditions = $funnel->conditions;

        if (!empty($conditions['changed_from']) && !in_array($conditions['changed_from'], $old_roles)) {
            return false;
        }

        if (!empty($conditions['changed_to']) && $conditions['changed_to'] !== $new_role) {
            return false;
        }

        $subscriberData = FunnelHelper::prepareUserData($user_id);

        if (!is_email($subscriberData['email'])) {
            error_log('Invalid email for user ID ' . $user_id);
            return false;
        }

        $subscriberData = wp_parse_args($subscriberData, $funnel->settings);
        $subscriberData['status'] = (!empty($subscriberData['subscription_status'])) ? $subscriberData['subscription_status'] : 'subscribed';
        unset($subscriberData['subscription_status']);

        (new FunnelProcessor())->startFunnelSequence($funnel, $subscriberData, [
            'source_trigger_name' => $this->triggerName,
            'source_ref_id' => $user_id
        ]);
    }
}

add_action('set_user_role', function ($user_id, $new_role, $old_roles) {
    $old_roles = (array) $old_roles;
    do_action('user_role_changed', [$user_id, $new_role, $old_roles]);

}, 10, 3);
