<?php

namespace HelloWP\FluentExtendTriggers\Includes;

if (!defined('ABSPATH')) {
    exit;
}

class ActionManager {

    public function __construct() {
        add_action('fluent_crm/after_init', [$this, 'registerActions']);
    }

    public function registerActions() {
        $actions = [
            '\HelloWP\FluentExtendTriggers\Actions\HW_Fluent_Post_Actions'
        ];
        foreach ($actions as $actionClass) {
            if (class_exists($actionClass)) {
                new $actionClass();
            }
        }
    }
}
