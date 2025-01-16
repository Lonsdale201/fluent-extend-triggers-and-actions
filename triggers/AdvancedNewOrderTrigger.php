<?php

namespace HelloWP\FluentExtendTriggers\Triggers;

use FluentCrm\App\Services\Funnel\BaseTrigger;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\App\Models\Subscriber;

use HelloWP\FluentExtendTriggers\Includes\CustomWooEvent;



class AdvancedNewOrderTrigger extends BaseTrigger
{
    public function __construct()
    {
        $this->triggerName = 'my_custom_woo_status_changed'; 
        $this->priority = 22;
        $this->actionArgNum = 3; 
        parent::__construct();
    }

    public function getTrigger()
    {
        return [
            'ribbon'      => 'Extend plugin',
            'category'    => __('WooCommerce', 'fluentcampaign-pro'),
            'label'       => __('Advanced New Order (Created)', 'hw-fluent-extendtriggers'),
            'description' => __('This funnel will start once a new order is created at checkout', 'hw-fluent-extendtriggers'),
            'icon'        => 'fc-icon-woo_new_order',
        ];
    }

    public function getFunnelSettingsDefaults()
    {
        return [
            'subscription_status' => 'subscribed'
        ];
    }

    public function getSettingsFields($funnel)
    {
        return [
            'title'     => __('Advanced Order (Created)', 'hw-fluent-extendtriggers'),
            'sub_title' => __('This Funnel will start once a new order is created at checkout', 'hw-fluent-extendtriggers'),
            'fields'    => [
                'subscription_status' => [
                    'type'        => 'option_selectors',
                    'option_key'  => 'editable_statuses',
                    'is_multiple' => false,
                    'label'       => __('Subscription Status', 'hw-fluent-extendtriggers'),
                    'placeholder' => __('Select Status', 'fluentcampaign-pro')
                ],
                'subscription_status_info' => [
                    'type'       => 'html',
                    'info'       => '<b>' . __('An Automated double-optin email will be sent for new subscribers', 'fluentcampaign-pro') . '</b>',
                    'dependency' => [
                        'depends_on' => 'subscription_status',
                        'operator'   => '=',
                        'value'      => 'pending'
                    ]
                ]
            ]
        ];
    }

    public function getFunnelConditionDefaults($funnel)
    {
        return [
            'product_ids' => [],
            'product_categories' => [],
            'payment_methods' => [],
            'shipping_methods' => [],
            'relation' => 'or',
            'run_multiple' => 'no'
        ];
    }


    public function getConditionFields($funnel)
    {
        return [
            'product_ids' => [
                'type'        => 'rest_selector',
                'option_key'  => 'woo_products',
                'is_multiple' => true,
                'label'       => __('Target Products', 'hw-fluent-extendtriggers'),
                'help'        => __('Select for which products this automation will run', 'hw-fluent-extendtriggers'),
                'inline_help' => __('Keep it blank to run to any product purchase', 'hw-fluent-extendtriggers')
            ],
            'product_name_contains' => [
                'type'        => 'input-text',
                'label'       => __('OR Product Name Contains', 'hw-fluent-extendtriggers'),
                'placeholder' => __('Type part of the product name here', 'hw-fluent-extendtriggers'),
                'help'        => __('Automation will run if the purchased product name includes this text (case-insensitive)', 'hw-fluent-extendtriggers'),
                'inline_help' => __('Leave blank to ignore product name', 'hw-fluent-extendtriggers')
            ],
            'product_categories' => [
                'type'        => 'rest_selector',
                'option_key'  => 'woo_categories',
                'is_multiple' => true,
                'label'       => __('OR Target Product Categories', 'hw-fluent-extendtriggers'),
                'help'        => __('Select for which product category the automation will run', 'hw-fluent-extendtriggers'),
                'inline_help' => __('Keep it blank to run to any category products', 'hw-fluent-extendtriggers')
            ],
            'payment_methods' => [
                'type'        => 'rest_selector',
                'option_key'  => 'woo_payment_methods',
                'is_multiple' => true,
                'label'       => __('Target Payment Methods', 'hw-fluent-extendtriggers'),
                'help'        => __('Select for which payment methods this automation will run', 'hw-fluent-extendtriggers'),
                'inline_help' => __('Keep it blank to run for any payment method', 'hw-fluent-extendtriggers')
            ],
            'shipping_methods' => [
                'type'        => 'rest_selector',
                'option_key'  => 'woo_shipping_methods',
                'is_multiple' => true,
                'label'       => __('Target Shipping Methods', 'hw-fluent-extendtriggers'),
                'help'        => __('Select for which shipping methods this automation will run', 'hw-fluent-extendtriggers'),
                'inline_help' => __('Keep it blank to run for any shipping method', 'hw-fluent-extendtriggers')
            ],
            'relation' => [
                'type'        => 'radio',
                'label'       => __('Relation Type', 'hw-fluent-extendtriggers'),
                'help'        => __('Select the relation type between payment and shipping methods', 'hw-fluent-extendtriggers'),
                'options'     => [
                    [
                        'id'    => 'or',
                        'title' => __('OR', 'hw-fluent-extendtriggers')
                    ],
                    [
                        'id'    => 'and',
                        'title' => __('AND', 'hw-fluent-extendtriggers')
                    ]
                ],
                'inline_help' => __('Use "OR" to match any method, "AND" to require both payment and shipping methods to match', 'hw-fluent-extendtriggers')
            ],
            'run_multiple' => [
                'type'        => 'yes_no_check',
                'label'       => '',
                'check_label' => __('Restart the Automation Multiple times for a contact for this event. (Only enable if you want to restart automation for the same contact)', 'hw-fluent-extendtriggers'),
                'inline_help' => __('If you enable, then it will restart the automation for a contact if the contact already in the automation. Otherwise, It will just skip if already exist', 'hw-fluent-extendtriggers')
            ]
        ];
    }

    public function handle($funnel, $originalArgs)
    {
        error_log('Handling funnel for order ID: ' . $originalArgs[0]);

        $orderId = $originalArgs[0];
        $order = wc_get_order($orderId);

        if (!$order) {
            error_log('Order not found for ID: ' . $orderId);
            return false;
        }

        $subscriberData = $this->prepareSubscriberData($order);
        $subscriberData = FunnelHelper::maybeExplodeFullName($subscriberData);

        // Ellenőrizzük, hogy létezik-e az email cím és érvényes-e
        if (empty($subscriberData['email']) || !is_email($subscriberData['email'])) {
            error_log('Invalid or missing email for order ID: ' . $orderId);
            return false;
        }

        if (!$this->isProcessable($funnel, $subscriberData, $order)) {
            error_log('Order ID ' . $orderId . ' will not be processed in funnel');
            return false;
        }

        $subscriberData = wp_parse_args($subscriberData, $funnel->settings);
        $subscriberData['status'] = (!empty($subscriberData['subscription_status'])) ? $subscriberData['subscription_status'] : 'subscribed';
        unset($subscriberData['subscription_status']);

        $this->updateOrCreateSubscriber($subscriberData);

        (new FunnelProcessor())->startFunnelSequence($funnel, $subscriberData, [
            'source_trigger_name' => $this->triggerName,
            'source_ref_id'       => $orderId
        ]);

        error_log('Funnel sequence started for order ID: ' . $orderId);
    }

    private function prepareSubscriberData($order)
    {
        $user_id = $order->get_user_id();
        $subscriberData = FunnelHelper::prepareUserData($user_id);

        if (empty($subscriberData['email'])) {
            $subscriberData['email'] = $order->get_billing_email();
        }

        $subscriberData['first_name'] = $order->get_billing_first_name();
        $subscriberData['last_name'] = $order->get_billing_last_name();
        $subscriberData['address_line_1'] = $order->get_billing_address_1();
        $subscriberData['address_line_2'] = $order->get_billing_address_2();
        $subscriberData['city'] = $order->get_billing_city();
        $subscriberData['state'] = $order->get_billing_state();
        $subscriberData['postal_code'] = $order->get_billing_postcode();
        $subscriberData['country'] = $order->get_billing_country();
        $subscriberData['phone'] = $order->get_billing_phone();

        return $subscriberData;
    }

    private function updateOrCreateSubscriber($subscriberData)
    {
        $subscriber = Subscriber::firstOrNew(['email' => $subscriberData['email']]);
        $subscriber->fill($subscriberData);
        $subscriber->save();
    }

    private function isProcessable($funnel, $subscriberData, $order)
{
    $conditions         = (array) $funnel->conditions;
    $productIds         = Arr::get($conditions, 'product_ids', []);
    $productNameFilter  = Arr::get($conditions, 'product_name_contains', '');
    $productCategories  = Arr::get($conditions, 'product_categories', []);
    $paymentMethods     = Arr::get($conditions, 'payment_methods', []);
    $shippingMethods    = Arr::get($conditions, 'shipping_methods', []);
    $relation           = Arr::get($conditions, 'relation', 'or');

    $orderProductIds         = [];
    $orderProductCategories  = [];

    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if (!$product) {
            continue;
        }

        $orderProductIds[] = $product->get_id();
        $orderProductCategories = array_merge(
            $orderProductCategories, 
            wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'ids'])
        );
    }

    if (!empty($productIds) && !array_intersect($productIds, $orderProductIds)) {
        error_log('Order products do not match funnel conditions for order ID: ' . $order->get_id());
        return false;
    }

    if ($productNameFilter) {
        // Stripos a case-insensitive 
        $matchedName = false;
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && stripos($product->get_name(), $productNameFilter) !== false) {
                $matchedName = true;
                break;
            }
        }
        if (!$matchedName) {
            return false;
        }
    }

    if (!empty($productCategories) && !array_intersect($productCategories, $orderProductCategories)) {
        return false;
    }

    $orderShippingMethods = [];
    foreach ($order->get_shipping_methods() as $shipping_item) {
        $orderShippingMethods[] = $shipping_item->get_method_id() . ':' . $shipping_item->get_instance_id();
    }

    $paymentMatch = empty($paymentMethods) || in_array($order->get_payment_method(), $paymentMethods);
    $shippingMatch = empty($shippingMethods) || !empty(array_intersect($shippingMethods, $orderShippingMethods));

    if ($relation == 'and' && (!$paymentMatch || !$shippingMatch)) {
        return false;
    } elseif ($relation == 'or' && (!$paymentMatch && !$shippingMatch)) {
        return false;
    }

    $subscriber = FunnelHelper::getSubscriber($subscriberData['email']);
    if ($subscriber && FunnelHelper::ifAlreadyInFunnel($funnel->id, $subscriber->id)) {
        $multipleRun = Arr::get($conditions, 'run_multiple') == 'yes';
        if ($multipleRun) {
            FunnelHelper::removeSubscribersFromFunnel($funnel->id, [$subscriber->id]);
        } else {
            return false;
        }
    }

    return true;
}

}
