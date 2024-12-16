<?php

namespace HelloWP\FluentExtendTriggers\Includes;
use HelloWP\FluentExtendTriggers\Includes\CustomWooEvent;
use HelloWP\FluentExtendTriggers\Includes\Dependency;

if (!defined('ABSPATH')) {
    exit;
}

class TriggerManager {

    public function __construct() {
        add_action('fluent_crm/after_init', [$this, 'registerTriggers'], 999);
    }

    public function registerTriggers() {

        $triggers = [];

        $triggers[] = '\\HelloWP\\FluentExtendTriggers\\Triggers\\HW_Fluent_Role_Changed';

        if (Dependency::isJetFormBuilderActive()) {
            $triggers[] = '\\HelloWP\\FluentExtendTriggers\\Triggers\\HW_Fluent_JFB_Submission';
            $triggers[] = '\\HelloWP\\FluentExtendTriggers\\Triggers\\HW_Fluent_JFB_Post_Insert';
            $triggers[] = '\\HelloWP\\FluentExtendTriggers\\Triggers\\HW_Fluent_JFB_Update_User';
        }

        if (Dependency::isJetReviewsActive()) {
            $triggers[] = '\\HelloWP\\FluentExtendTriggers\\Triggers\\HW_Fluent_JetReview_Submit';
        }

        if (Dependency::isWooCommerceActive() && Dependency::isFluentCRMProActive()) {
            $triggers[] = '\\HelloWP\\FluentExtendTriggers\\Triggers\\AdvancedNewOrderTrigger';
            $triggers[] = '\\HelloWP\\FluentExtendTriggers\\Triggers\\ReviewAddedTrigger';

            new CustomWooEvent();
        }

        foreach ($triggers as $triggerClass) {
            if (class_exists($triggerClass)) {
                new $triggerClass();
            }
        }
    }
}


add_filter('fluent_crm/woo_trigger_names', function($names) {
    $names[] = 'my_custom_woo_status_changed';
    return $names;
}, 99);
