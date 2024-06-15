<?php

namespace HelloWP\FluentExtendTriggers\JEModules;

class HW_JE_Contact_Users_With_Status extends \Jet_Engine_Base_Macros {

    public function macros_tag() {
        return 'fluent_crm_users_with_status';
    }

    public function macros_name() {
        return __('Fluent CRM Users with Status', 'hw-fluent-extendtriggers');
    }

    public function macros_args() {
        $statuses = fluentcrm_subscriber_statuses(true);
        $status_options = array();

        foreach ($statuses as $status) {
            $status_options[$status['id']] = $status['title'];
        }

        return array(
            'contact_status' => array(
                'label'   => __('Contact Status', 'hw-fluent-extendtriggers'),
                'type'    => 'select',
                'options' => $status_options,
                'default' => 'subscribed'
            ),
        );
    }

    public function macros_callback($args = array()) {
        $contact_status = !empty($args['contact_status']) ? $args['contact_status'] : 'subscribed';

        $users = get_users(array('fields' => array('ID')));
        $crm_users = array();

        $contacts = fluentCrmApi('contacts')->all();

        foreach ($users as $user) {
            foreach ($contacts as $contact) {
                if (isset($contact->user_id) && $contact->user_id == $user->ID && $contact->status == $contact_status) {
                    $crm_users[] = $user->ID;
                    break;
                }
            }
        }

        if (empty($crm_users)) {
            return __('No CRM users found', 'hw-fluent-extendtriggers');
        }

        return implode(',', $crm_users);
    }
}