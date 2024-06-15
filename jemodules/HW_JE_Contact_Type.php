<?php

namespace HelloWP\FluentExtendTriggers\JEModules;

class HW_JE_Contact_Type extends \Jet_Engine_Base_Macros {

    public function macros_tag() {
        return 'fluent_crm_users_with_contact_type';
    }

    public function macros_name() {
        return __('Fluent CRM Users with Contact Type', 'hw-fluent-extendtriggers');
    }

    public function macros_args() {
        $contact_types = fluentcrm_contact_types(true);
        $type_options = array();

        foreach ($contact_types as $type) {
            $type_options[$type['id']] = $type['title'];
        }

        return array(
            'contact_type' => array(
                'label'   => __('Contact Type', 'hw-fluent-extendtriggers'),
                'type'    => 'select',
                'options' => $type_options,
                'default' => key($type_options)
            ),
        );
    }

    public function macros_callback($args = array()) {
        $contact_type = !empty($args['contact_type']) ? $args['contact_type'] : key($args['contact_type']);

        $users = get_users(array('fields' => array('ID')));
        $crm_users = array();

        $contactApi = FluentCrmApi('contacts');

        foreach ($users as $user) {
            $contact = $contactApi->getContactByUserRef($user->ID);
            if ($contact && $contact->contact_type == $contact_type) {
                $crm_users[] = $user->ID;
            }
        }

        if (empty($crm_users)) {
            return __('No CRM users found', 'hw-fluent-extendtriggers');
        }

        return implode(',', $crm_users);
    }
}