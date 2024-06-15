<?php

namespace HelloWP\FluentExtendTriggers\Includes;

if (!defined('ABSPATH')) {
    exit;
}

class TriggerManager {

    public function __construct() {
        add_action('fluent_crm/after_init', [$this, 'registerTriggers']);
    }

    public function registerTriggers() {
        $triggers = [
            '\HelloWP\FluentExtendTriggers\Triggers\HW_Fluent_JFB_Submission',
            '\HelloWP\FluentExtendTriggers\Triggers\HW_Fluent_JFB_Post_Insert'
        ];
        foreach ($triggers as $triggerClass) {
            if (class_exists($triggerClass)) {
                new $triggerClass();
            } else {
            }
        }
    }
    

}
