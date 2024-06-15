<?php

namespace HelloWP\FluentExtendTriggers\JEModules;

class HW_JE_Contact_User_Query extends \Jet_Engine_Base_Macros {

    public function macros_tag() {
        return 'fluent_crm_wp_users';
    }

    public function macros_name() {
        return __('Fluent CRM / WP Users', 'hw-fluent-extendtriggers');
    }

    public function macros_args() {
        return array();
    }

    public function macros_callback($args = array()) {
        $users = get_users(array('fields' => array('ID')));
        $crm_users = array();

        $contacts = fluentCrmApi('contacts')->all();

        foreach ($users as $user) {
            foreach ($contacts as $contact) {
                if ($contact->user_id == $user->ID) {
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
