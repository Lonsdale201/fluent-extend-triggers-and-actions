<?php

namespace HelloWP\FluentExtendTriggers\SmartCodes;

use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\FunnelSubscriber;
use WC_Order;

/**
 * Class WooOrderMeta
 *
 *   {{woo_order.meta.OMETA_KEY}}
 *
 * Replace OMETA_KEY with any WooCommerce order meta key, for example 
 * '_billing_first_name' or 'my_custom_meta'.
 */
class WooOrderMeta
{
    public function __construct()
    {
        add_action('fluent_crm/after_init', [$this, 'registerSmartCode']);
    }

    /**
     * Registers the SmartCode via the FluentCRM "extender" API.
     */
    public function registerSmartCode()
    {
        $key        = 'woo_order_meta';   
        $title      = 'Current order - Meta';
        $shortCodes = [
            'meta.OMETA_KEY' => 'Custom WC Order meta value (replace OMETA_KEY)'
        ];
        $callback   = [$this, 'handleSmartCode'];

        FluentCrmApi('extender')->addSmartCode($key, $title, $shortCodes, $callback);
    }

    /**
     * handleSmartCode
     *
     * @param string                           $code         The SmartCode group key (here: "woo_order")
     * @param string                           $valueKey     Example: "meta._billing_first_name"
     * @param string                           $defaultValue Default value if meta is not found
     * @param \FluentCrm\App\Models\Subscriber $subscriber   The current contact during funnel execution
     *
     * @return string
     */
    public function handleSmartCode($code, $valueKey, $defaultValue, $subscriber)
    {
        // Only handle if it starts with "meta."
        if (strpos($valueKey, 'meta.') !== 0) {
            return $defaultValue;
        }

        // Strip off 'meta.' to get the actual meta key
        $metaKey = substr($valueKey, 5); // 5 = strlen('meta.')

        // Attempt to load the "current order" if available,
        // otherwise load the last order
        $order = $this->getCurrentOrder($subscriber);
        if (!$order || !$order->get_id()) {
            return $defaultValue;
        }

        // Woo HPOS
        $metaValue = $order->get_meta($metaKey);

        if (!$metaValue) {
            return $defaultValue;
        }

        return (string) $metaValue;
    }

    /**
     * getCurrentOrder
     * 
     * In funnel context, returns the "current" order if present;
     * otherwise returns the last order.
     */
    private function getCurrentOrder(Subscriber $subscriber)
    {
        if (empty($subscriber->funnel_subscriber_id)) {
            return $this->getLastOrder($subscriber);
        }

        $funnelSub = FunnelSubscriber::find($subscriber->funnel_subscriber_id);
        if (!$funnelSub || !$funnelSub->source_ref_id) {
            return $this->getLastOrder($subscriber);
        }

        try {
            $order = new WC_Order($funnelSub->source_ref_id);
        } catch (\Exception $exception) {
            return false;
        }

        if (!$order || !$order->get_id()) {
            return false;
        }

        return $order;
    }

    /**
     * getLastOrder
     *
     * Attempts to retrieve the last order based on user ID or the Commerce plugin.
     */
    private function getLastOrder(Subscriber $subscriber)
    {
        $wpUserId = $subscriber->getWpUserId();
        if ($wpUserId) {
            $customer = new \WC_Customer($wpUserId);
            $lastOrder = $customer->get_last_order();
            if ($lastOrder && $lastOrder->get_id()) {
                return $lastOrder;
            }
        }

        return false;
    }
}
