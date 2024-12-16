<?php

namespace HelloWP\FluentExtendTriggers\Includes;

use HelloWP\FluentExtendTriggers\Includes\CustomWooEvent;

if (!defined('ABSPATH')) {
    exit;
}

class Dependency {

   /**
     * Check if JetFormBuilder is active.
     *
     * @return bool
     */
    public static function isJetFormBuilderActive() {
        return class_exists('Jet_Form_Builder\\Plugin');
    }

    /**
     * Check if JetReviews is active.
     *
     * @return bool
     */
    public static function isJetReviewsActive() {
        return class_exists('Jet_Reviews');
    }

    /**
     * Check if WooCommerce is active.
     *
     * @return bool
     */
    public static function isWooCommerceActive() {
        return class_exists('WooCommerce');
    }

    /**
     * Check if FluentCRM Pro is active.
     *
     * @return bool
     */
    public static function isFluentCRMProActive() {
        return defined('FLUENTCAMPAIGN_PLUGIN_VERSION') && version_compare(FLUENTCAMPAIGN_PLUGIN_VERSION, '2.8.0', '>=');
    }

}
