<?php

namespace HelloWP\FluentExtendTriggers\Includes;
use HelloWP\FluentExtendTriggers\Includes\CustomWooEvent;

if (!defined('ABSPATH')) {
    exit;
}

class TriggerManager {

    public function __construct() {
        add_action('fluent_crm/after_init', [$this, 'registerTriggers'], 999);
    }

    public function registerTriggers() {
        
        $triggers = [];

         $triggers[] = '\HelloWP\FluentExtendTriggers\Triggers\HW_Fluent_Role_Changed';

        if ($this->isJetFormBuilderActive()) {
            $triggers[] = '\HelloWP\FluentExtendTriggers\Triggers\HW_Fluent_JFB_Submission';
            $triggers[] = '\HelloWP\FluentExtendTriggers\Triggers\HW_Fluent_JFB_Post_Insert';
            $triggers[] = '\HelloWP\FluentExtendTriggers\Triggers\HW_Fluent_JFB_Update_User';
        }

        if ($this->isJetReviewsActive()) {
            $triggers[] = '\HelloWP\FluentExtendTriggers\Triggers\HW_Fluent_JetReview_Submit';
        }

        if ($this->isWooCommerceActive()) {
            $triggers[] = '\HelloWP\FluentExtendTriggers\Triggers\AdvancedNewOrderTrigger';
            $triggers[] = '\HelloWP\FluentExtendTriggers\Triggers\ReviewAddedTrigger';

            new CustomWooEvent();
        }

        foreach ($triggers as $triggerClass) {
            if (class_exists($triggerClass)) {
                new $triggerClass();
            }
        }
    }

    private function isJetFormBuilderActive() {
        return class_exists('Jet_Form_Builder\Plugin');
    }

    private function isJetReviewsActive() {
        return class_exists('Jet_Reviews');
    }

    private function isWooCommerceActive() {
        return class_exists('WooCommerce');
    }
}


add_filter('fluent_crm/woo_trigger_names', function($names) {
    $names[] = 'my_custom_woo_status_changed';
    return $names;
}, 99);
