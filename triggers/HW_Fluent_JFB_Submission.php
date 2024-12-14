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
            'first_name_field' => '',
            'last_name_field' => '',
            'phone_field' => '',
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
                'wrapper_class' => 'fc_half_field',
                'type'          => 'input-text',
                'label'         => __('Email Field Mapping', 'hw-fluent-extendtriggers'),
                'placeholder'   => __('Form Field Name', 'hw-fluent-extendtriggers'),
                'inline_help'   => __('Please provide the "Form Field Name" for the email field in your form. For an existing contact, this data will not be overwritten.', 'hw-fluent-extendtriggers')
            ],
            'first_name_field' => [
                'wrapper_class' => 'fc_half_field',
                'type'          => 'input-text',
                'label'         => __('First Name Field Mapping', 'hw-fluent-extendtriggers'),
                'placeholder'   => __('Form Field Name', 'hw-fluent-extendtriggers'),
                'inline_help'   => __('Please provide the "Form Field Name" for the first name field in your form. For an existing contact, this data will not be overwritten.', 'hw-fluent-extendtriggers')
            ],
            'last_name_field' => [
                'wrapper_class' => 'fc_half_field',
                'type'          => 'input-text',
                'label'         => __('Last Name Field Mapping', 'hw-fluent-extendtriggers'),
                'placeholder'   => __('Form Field Name', 'hw-fluent-extendtriggers'),
                'inline_help'   => __('Please provide the "Form Field Name" for the last name field in your form. For an existing contact, this data will not be overwritten.', 'hw-fluent-extendtriggers')
            ],
            'phone_field' => [
                'wrapper_class' => 'fc_half_field',
                'type'          => 'input-text',
                'label'         => __('Phone Field Mapping', 'hw-fluent-extendtriggers'),
                'placeholder'   => __('Form Field Name', 'hw-fluent-extendtriggers'),
                'inline_help'   => __('Please provide the "Form Field Name" for the phone field in your form. For an existing contact, this data will not be overwritten.', 'hw-fluent-extendtriggers')
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
        $is_success = $handler->is_success;
        $formId = isset($handler->form_id) ? $handler->form_id : '';
        $formData = $_POST;
        $userId = get_current_user_id();

        error_log('Form ID: ' . $formId);
        error_log('Is success: ' . $is_success);

        // Get the field names from funnel conditions
        $emailField = Arr::get($funnel->conditions, 'email_field');
        $firstNameField = Arr::get($funnel->conditions, 'first_name_field');
        $lastNameField = Arr::get($funnel->conditions, 'last_name_field');
        $phoneField = Arr::get($funnel->conditions, 'phone_field');

        error_log('Email field mapping: ' . $emailField);
        error_log('First name field mapping: ' . $firstNameField);
        error_log('Last name field mapping: ' . $lastNameField);
        error_log('Phone field mapping: ' . $phoneField);

        if (!$emailField) {
            error_log('Email field not set in funnel conditions');
            return false;
        }

        // Log the entire form data to inspect its structure
        error_log('Form data: ' . print_r($formData, true));

        // Get the values from the form submission
        $email = isset($formData[$emailField]) ? sanitize_email($formData[$emailField]) : '';
        $firstName = isset($formData[$firstNameField]) ? sanitize_text_field($formData[$firstNameField]) : '';
        $lastName = isset($formData[$lastNameField]) ? sanitize_text_field($formData[$lastNameField]) : '';
        $phone = isset($formData[$phoneField]) ? sanitize_text_field($formData[$phoneField]) : '';

        error_log('Email from form: ' . $email);
        error_log('First name from form: ' . $firstName);
        error_log('Last name from form: ' . $lastName);
        error_log('Phone from form: ' . $phone);

        if (!is_email($email)) {
            error_log('Invalid email: ' . $email);
            return false;
        }

        // If user is not logged in, use the fields from form submission
        if (!$userId) {
            $subscriberData = [
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone
            ];
        } else {
            $subscriberData = FunnelHelper::prepareUserData($userId);
            if (empty($subscriberData['email'])) {
                $subscriberData['email'] = $email;
            }
            if (empty($subscriberData['first_name']) && $firstName) {
                $subscriberData['first_name'] = $firstName;
            }
            if (empty($subscriberData['last_name']) && $lastName) {
                $subscriberData['last_name'] = $lastName;
            }
            if (empty($subscriberData['phone']) && $phone) {
                $subscriberData['phone'] = $phone;
            }
        }

        error_log('Subscriber data: ' . print_r($subscriberData, true));

        if (!$this->isProcessable($funnel, $formId, $subscriberData)) {
            error_log('Form submission is not processable');
            return false;
        }

        // Ellenőrizd, hogy a felhasználó már létezik-e a rendszerben
        $existingSubscriber = Subscriber::where('email', $subscriberData['email'])->first();
        if ($existingSubscriber) {
            error_log('Existing subscriber found: ' . print_r($existingSubscriber->toArray(), true));
            $subscriberData = array_merge($existingSubscriber->toArray(), $subscriberData);
        }

        $subscriberData = wp_parse_args($subscriberData, $funnel->settings);
        $subscriberData['status'] = (!empty($subscriberData['subscription_status'])) ? $subscriberData['subscription_status'] : 'subscribed';
        unset($subscriberData['subscription_status']);

        // Register the contact in FluentCRM
        $subscriber = Subscriber::query()->updateOrCreate(
            ['email' => $subscriberData['email']],
            $subscriberData
        );

        error_log('Subscriber: ' . print_r($subscriber->toArray(), true));

        (new FunnelProcessor())->startFunnelSequence($funnel, $subscriber->toArray(), [
            'source_trigger_name' => $this->triggerName,
            'source_ref_id' => $formId
        ]);

        error_log('Funnel sequence started for form ID: ' . $formId);
    }

    private function isProcessable($funnel, $formId, $subscriberData)
    {
        $conditions = $funnel->conditions;

        if (!empty($conditions['form_ids']) && !in_array($formId, $conditions['form_ids'])) {
            error_log('Form ID ' . $formId . ' not in funnel conditions');
            return false;
        }

        $subscriber = FunnelHelper::getSubscriber($subscriberData['email']);

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

add_action('jet-form-builder/form-handler/after-send', function($handler) {
    do_action('jetformbuilder_form_submission', $handler);
}, 10);
