<?php

namespace HelloWP\FluentExtendTriggers\Actions;

use FluentCrm\App\Services\Funnel\BaseAction;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\Framework\Support\Arr;
use WC_Order;
use WC_Product;

class HW_Fluent_Woo_Place_Order extends BaseAction {

    public function __construct()
    {
        $this->actionName = 'place_order_action';
        $this->priority = 20;
        parent::__construct();
    }

    public function getBlock()
    {
        return [
            'category'    => __('WooCommerce', 'hw-fluent-extendtriggers'),
            'title'       => __('Place Order', 'hw-fluent-extendtriggers'),
            'description' => __('Place an order for the selected product', 'hw-fluent-extendtriggers'),
            'icon'        => 'fc-icon-trigger',
        ];
    }

    public function getBlockFields()
    {
        $orderStatuses = wc_get_order_statuses();
        $formattedStatuses = [];
        foreach ($orderStatuses as $statusId => $statusName) {
            $formattedStatuses[] = [
                'id' => $statusId,
                'title' => $statusName
            ];
        }

        return [
            'title'     => __('Place Order', 'hw-fluent-extendtriggers'),
            'sub_title' => __('Place an order for the selected product', 'hw-fluent-extendtriggers'),
            'fields'    => [
                'product_id' => [
                    'type'        => 'rest_selector',
                    'option_key'  => 'woo_products',
                    'is_multiple' => false,
                    'clearable'   => true,
                    'label'       => __('Select Product', 'hw-fluent-extendtriggers'),
                    'placeholder' => __('Select Product', 'hw-fluent-extendtriggers')
                ],
                'quantity' => [
                    'type'    => 'input-number',
                    'label'   => __('Quantity', 'hw-fluent-extendtriggers'),
                    'default' => 1,
                    'wrapper_class' => 'fc_2col_inline pad-r-20'
                ],
                'order_status' => [
                    'type'        => 'select',
                    'label'       => __('Select Order Status', 'hw-fluent-extendtriggers'),
                    'options'     => $formattedStatuses,
                    'is_multiple' => false,
                    'clearable'   => true,
                    'placeholder' => __('Select Status', 'hw-fluent-extendtriggers')
                ],
                'order_date' => [
                    'label'       => __('Order Date', 'hw-fluent-extendtriggers'),
                    'type'        => 'date_time',
                    'placeholder' => __('Select Date & Time', 'hw-fluent-extendtriggers'),
                    'inline_help' => __('Set the date and time for the order. If not set, the current date and time will be used.', 'hw-fluent-extendtriggers')
                ],
                'add_admin_note' => [
                    'type'    => 'radio',
                    'label'   => __('Add Admin Order Note', 'hw-fluent-extendtriggers'),
                    'options' => [
                        [
                            'id'    => 'no',
                            'title' => __('No', 'hw-fluent-extendtriggers')
                        ],
                        [
                            'id'    => 'yes',
                            'title' => __('Yes', 'hw-fluent-extendtriggers')
                        ]
                    ],
                    'default' => 'no'
                ],
                'admin_note' => [
                    'type'       => 'input-text-popper',
                    'field_type' => 'textarea',
                    'label'      => __('Order Note', 'hw-fluent-extendtriggers'),
                    'help'       => __('Type the note that you want to add to the reference order. You can also use smart tags', 'hw-fluent-extendtriggers'),
                    'dependency' => [
                        'depends_on' => 'add_admin_note',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
            ]
        ];
    }

    public function handle($subscriber, $sequence, $funnelSubscriberId, $funnelMetric)
    {
        $settings = $sequence->settings;
        $userId = $subscriber->getWpUserId();

        $productId = Arr::get($settings, 'product_id');
        $quantity = Arr::get($settings, 'quantity', 1);
        $orderStatus = Arr::get($settings, 'order_status', 'wc-pending');
        $orderDate = Arr::get($settings, 'order_date', current_time('mysql'));
        $addAdminNote = Arr::get($settings, 'add_admin_note', 'no');
        $adminNote = Arr::get($settings, 'admin_note', '');

        if (!$productId || !$quantity || !$orderStatus) {
            $funnelMetric->notes = __('Funnel Skipped because product ID, quantity, or order status is missing', 'hw-fluent-extendtriggers');
            $funnelMetric->save();
            FunnelHelper::changeFunnelSubSequenceStatus($funnelSubscriberId, $sequence->id, 'skipped');
            return false;
        }

        if (!$userId) {
            return false;
        }

        $order = wc_create_order([
            'customer_id' => $userId
        ]);

        $user = get_user_by('ID', $userId);
        if ($user) {
            $billingAddress = [
                'first_name' => get_user_meta($userId, 'billing_first_name', true),
                'last_name'  => get_user_meta($userId, 'billing_last_name', true),
                'address_1'  => get_user_meta($userId, 'billing_address_1', true),
                'address_2'  => get_user_meta($userId, 'billing_address_2', true),
                'city'       => get_user_meta($userId, 'billing_city', true),
                'postcode'   => get_user_meta($userId, 'billing_postcode', true),
                'country'    => get_user_meta($userId, 'billing_country', true),
                'state'      => get_user_meta($userId, 'billing_state', true),
                'email'      => $user->user_email,
                'phone'      => get_user_meta($userId, 'billing_phone', true)
            ];

            $shippingAddress = [
                'first_name' => get_user_meta($userId, 'shipping_first_name', true),
                'last_name'  => get_user_meta($userId, 'shipping_last_name', true),
                'address_1'  => get_user_meta($userId, 'shipping_address_1', true),
                'address_2'  => get_user_meta($userId, 'shipping_address_2', true),
                'city'       => get_user_meta($userId, 'shipping_city', true),
                'postcode'   => get_user_meta($userId, 'shipping_postcode', true),
                'country'    => get_user_meta($userId, 'shipping_country', true),
                'state'      => get_user_meta($userId, 'shipping_state', true)
            ];

            $order->set_address($billingAddress, 'billing');
            $order->set_address($shippingAddress, 'shipping');
        }

        $product = wc_get_product($productId);
        if ($product instanceof WC_Product) {
            $order->add_product($product, $quantity);
            $order->set_status($orderStatus);
            $order->set_date_created($orderDate);
            $order->calculate_totals();

            if ($addAdminNote === 'yes' && !empty($adminNote)) {
                $order->add_order_note($adminNote, false); // false indicates this is an admin note
            }

            $order->save();

            $funnelMetric->notes = __('Order placed successfully', 'hw-fluent-extendtriggers');
            $funnelMetric->save();
            FunnelHelper::changeFunnelSubSequenceStatus($funnelSubscriberId, $sequence->id, 'completed');
        } else {
            $funnelMetric->notes = __('Invalid product ID', 'hw-fluent-extendtriggers');
            $funnelMetric->save();
            FunnelHelper::changeFunnelSubSequenceStatus($funnelSubscriberId, $sequence->id, 'skipped');
        }
    }
}
