<?php

namespace HelloWP\FluentExtendTriggers\JEModules;

class HW_JE_Contact_Users_With_List extends \Jet_Engine_Base_Macros {

    public function macros_tag() {
        return 'fluent_crm_users_with_list';
    }

    public function macros_name() {
        return 'Fluent CRM Users with List';
    }

    public function macros_args() {
        $listApi = FluentCrmApi('lists');
        $lists = $listApi->all();
        $list_options = array();

        foreach ($lists as $list) {
            $list_options[$list->id] = $list->title;
        }

        return array(
            'contact_list' => array(
                'label'   => 'Contact List',
                'type'    => 'select',
                'options' => $list_options,
                'default' => key($list_options)
            ),
        );
    }

    public function macros_callback($args = array()) {
        $contact_list = !empty($args['contact_list']) ? $args['contact_list'] : key($args['contact_list']);

        $users = get_users(array('fields' => array('ID')));
        $crm_users = array();

        $contactApi = FluentCrmApi('contacts')->getInstance();
        $contacts = $contactApi->filterByLists([$contact_list])->get();

        foreach ($users as $user) {
            foreach ($contacts as $contact) {
                if (is_object($user) && isset($user->ID) && isset($contact->user_id)) {
                    if ($contact->user_id == $user->ID) {
                        $crm_users[] = $user->ID;
                        break;
                    }
                }
            }
        }

        if (empty($crm_users)) {
            return 'No CRM users found';
        }

        return implode(',', $crm_users);
    }
}

