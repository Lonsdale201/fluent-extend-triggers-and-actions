<?php

namespace HelloWP\FluentExtendTriggers\Includes;

use HelloWP\FluentExtendTriggers\Includes\Dependency;

if (!defined('ABSPATH')) {
    exit;
}

class ActionManager {

    public function __construct() {
        add_action('fluent_crm/after_init', [$this, 'registerActions']);
    }

    public function registerActions() {
        $actions = [
            '\\HelloWP\\FluentExtendTriggers\\Actions\\HW_Fluent_Post_Actions'
        ];

        if (Dependency::isWooCommerceActive() && Dependency::isFluentCRMProActive()) {
            $actions[] = '\\HelloWP\\FluentExtendTriggers\\Actions\\HW_Fluent_Woo_Place_Order';
        }

        foreach ($actions as $actionClass) {
            if (class_exists($actionClass)) {
                new $actionClass();
            }
        }
    }
}
