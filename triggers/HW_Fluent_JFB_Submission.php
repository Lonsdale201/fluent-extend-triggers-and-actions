<?php

namespace HelloWP\FluentExtendTriggers\Triggers;

use FluentCrm\App\Services\Funnel\BaseTrigger;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\App\Models\Subscriber;

class HW_Fluent_JFB_Submission extends BaseTrigger
{
    public function __construct()
    {
        $this->triggerName = 'jetformbuilder_form_submission';
        $this->priority = 22;
        $this->actionArgNum = 1;
        parent::__construct();
    }

    public function getTrigger()
    {
        return [
            'category'    => __('JetFormBuilder', 'hw-fluent-extendtriggers'),
            'label'       => __('JetFormBuilder Form Submission', 'hw-fluent-extendtriggers'),
            'description' => __('This trigger fires when a JetFormBuilder form is successfully submitted.', 'hw-fluent-extendtriggers'),
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
            'title'     => __('JetFormBuilder Form Submission', 'hw-fluent-extendtriggers'),
            'sub_title' => __('This will start when a JetFormBuilder form is successfully submitted', 'hw-fluent-extendtriggers'),
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
            'email_field' => '',
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
            'email_field' => [
                'type'          => 'input-text',
                'label'         => __('Email Field Mapping', 'hw-fluent-extendtriggers'),
                'placeholder'   => __('Form Field Name', 'hw-fluent-extendtriggers'),
                'inline_help'   => __('Provide the name of the form field that collects the email address. This will be used to register the submitter in the CRM. Optional, not mandatory.', 'hw-fluent-extendtriggers')
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

        $emailField = Arr::get($funnel->conditions, 'email_field');
        $email = ($emailField && isset($formData[$emailField])) ? sanitize_email($formData[$emailField]) : '';

        $subscriberData = [];
        if (is_email($email)) {
            $subscriberData['email'] = $email;
        } elseif (get_current_user_id()) {
            $subscriberData = FunnelHelper::prepareUserData(get_current_user_id());
        } else {
            return false;
        }

        if (!$this->isProcessable($funnel, $formId, $subscriberData)) {
            error_log('Form submission is not processable');
            return false;
        }

        $subscriber = null;
        if (!empty($subscriberData['email'])) {
            $subscriber = Subscriber::query()->updateOrCreate(
                ['email' => $subscriberData['email']],
                $subscriberData
            );
            error_log('Subscriber created or updated: ' . print_r($subscriber->toArray(), true));
        }

        if ($subscriber) {
            (new FunnelProcessor())->startFunnelSequence($funnel, $subscriber->toArray(), [
                'source_trigger_name' => $this->triggerName,
                'source_ref_id' => $formId
            ]);
        } else {
            return false;
        }
    }


    private function isProcessable($funnel, $formId, $subscriberData)
    {
        $conditions = $funnel->conditions;

        if (!empty($conditions['form_ids']) && is_array($conditions['form_ids']) && !in_array($formId, $conditions['form_ids'])) {
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

add_action('jet-form-builder/form-handler/after-send', function($handler) {
    do_action('jetformbuilder_form_submission', $handler);
}, 10);
