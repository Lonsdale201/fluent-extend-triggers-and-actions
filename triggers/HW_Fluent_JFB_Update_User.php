<?php

namespace HelloWP\FluentExtendTriggers\Triggers;

use FluentCrm\App\Services\Funnel\BaseTrigger;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\App\Models\Subscriber;

class HW_Fluent_JFB_Update_User extends BaseTrigger
{
    public function __construct()
    {
        $this->triggerName = 'jetformbuilder_update_user';
        $this->priority = 22;
        $this->actionArgNum = 1;
        parent::__construct();
    }

    public function getTrigger()
    {
        return [
            'category'    => __('JetFormBuilder', 'hw-fluent-extendtriggers'),
            'label'       => __('JetFormBuilder Update User', 'hw-fluent-extendtriggers'),
            'description' => __('This trigger fires when a JetFormBuilder form successfully updates a user.', 'hw-fluent-extendtriggers'),
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
            'title'     => __('JetFormBuilder Update User', 'hw-fluent-extendtriggers'),
            'sub_title' => __('This will start when a JetFormBuilder form successfully updates a user', 'hw-fluent-extendtriggers'),
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
            'form_ids' => [],
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
        $handler = $originalArgs[0];
        $formId = isset($handler->form_id) ? $handler->form_id : '';
        $formData = $_POST;
        $userId = get_current_user_id();

        error_log('Form ID: ' . $formId);

        if (!$userId) {
            error_log('No user logged in, skipping trigger.');
            return false;
        }

        // Ensure form actions are logged
        if (!isset($handler->action_handler) || !is_array($handler->action_handler->form_actions)) {
            error_log('Form actions is not an array or action_handler is not set.');
            return false;
        }

        error_log('Form Actions: ' . print_r($handler->action_handler->form_actions, true));

        // Check if the form has an update user action
        $hasUpdateUserAction = false;
        foreach ($handler->action_handler->form_actions as $action) {
            if (is_object($action) && get_class($action) === 'Jet_Form_Builder\Actions\Types\Update_User') {
                $hasUpdateUserAction = true;
                break;
            }
        }

        if (!$hasUpdateUserAction) {
            error_log('Form does not have an update user action, skipping trigger.');
            return false;
        }

        // Log the entire form data to inspect its structure
        error_log('Form data: ' . print_r($formData, true));

        $subscriberData = FunnelHelper::prepareUserData($userId);

        // Log the current subscriber data
        error_log('Subscriber data: ' . print_r($subscriberData, true));

        // Find existing subscriber or create a new instance
        $subscriber = Subscriber::where('user_id', $subscriberData['user_id'])->first();
        if (!$subscriber) {
            $subscriber = new Subscriber();
        }

        // Update subscriber data
        $subscriber->fill($subscriberData);
        $subscriber->save();

        error_log('Subscriber: ' . print_r($subscriber->toArray(), true));

        (new FunnelProcessor())->startFunnelSequence($funnel, $subscriber->toArray(), [
            'source_trigger_name' => $this->triggerName,
            'source_ref_id' => $formId
        ]);

        error_log('Funnel sequence started for form ID: ' . $formId);
    }

    private function isProcessable($funnel, $formId, $userId)
    {
        $conditions = $funnel->conditions;

        if (!empty($conditions['form_ids']) && !in_array($formId, $conditions['form_ids'])) {
            error_log('Form ID ' . $formId . ' not in funnel conditions');
            return false;
        }

        $subscriber = Subscriber::where('user_id', $userId)->first();

        if ($subscriber && FunnelHelper::ifAlreadyInFunnel($funnel->id, $subscriber->id)) {
            $multipleRun = Arr::get($conditions, 'run_multiple') == 'yes';
            if ($multipleRun) {
                FunnelHelper::removeSubscribersFromFunnel($funnel->id, [$subscriber->id]);
                error_log('Removed subscriber from funnel for multiple run');
            } else {
                error_log('Subscriber already in funnel and multiple run not allowed');
                return false;
            }
        }

        return true;
    }
}

add_action('jet-form-builder/form-handler/after-send', function($handler, $is_success) {
    // Ensure that the form has an update user action
    error_log('JetFormBuilder form handler after-send action triggered');
    if (!isset($handler->action_handler) || !is_array($handler->action_handler->form_actions)) {
        error_log('Form actions is not an array or action_handler is not set.');
        return;
    }

    foreach ($handler->action_handler->form_actions as $action) {
        if (is_object($action) && get_class($action) === 'Jet_Form_Builder\Actions\Types\Update_User') {
            error_log('Update User action found in form actions');
            do_action('jetformbuilder_update_user', $handler);
            return;
        }
    }
    error_log('No Update User action found in form actions');
}, 10, 2);
