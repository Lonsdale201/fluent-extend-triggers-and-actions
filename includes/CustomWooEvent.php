<?php

namespace HelloWP\FluentExtendTriggers\Includes;

if (!defined('ABSPATH')) {
    exit;
}

class CustomWooEvent {

    public function __construct() {
        add_action('woocommerce_order_status_changed', [$this, 'handleOrderStatusChanged'], 10, 3);
    }

    public function handleOrderStatusChanged($orderId, $fromStatus, $toStatus) {
        error_log('woocommerce_order_status_changed triggered for order ID: ' . $orderId);
        // do_action('ext_custom_woo_status_ch', $orderId, $fromStatus, $toStatus);
        do_action('my_custom_woo_status_changed', $orderId, $fromStatus, $toStatus);
    }
}
