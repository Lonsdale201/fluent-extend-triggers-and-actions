<?php

namespace HelloWP\FluentExtendTriggers\JEModules;

class MacrosManager {

    public function __construct() {
        if (class_exists('Jet_Engine')) {
            add_action('jet-engine/register-macros', [$this, 'registerMacros']);
        }
    }

    public function registerMacros() {
        $macros = [
            '\HelloWP\FluentExtendTriggers\JEModules\HW_JE_Contact_Type',
            '\HelloWP\FluentExtendTriggers\JEModules\HW_JE_Contact_User_Query',
            '\HelloWP\FluentExtendTriggers\JEModules\HW_JE_Contact_Users_With_List',
            '\HelloWP\FluentExtendTriggers\JEModules\HW_JE_Contact_Users_With_Status'
        ];

        foreach ($macros as $macroClass) {
            if (class_exists($macroClass)) {
                new $macroClass();
            }
        }
    }
}
