<?php
namespace HelloWP\FluentExtendTriggers\Includes;

if (!defined('ABSPATH')) {
    exit;
}

class CustomControllers
{
    public function __construct()
    {
        add_filter('fluentcrm_ajax_options_jfb_forms', [$this, 'get_jfb_forms'], 10, 3);
        add_filter('fluentcrm_ajax_options_post_statuses', [$this, 'get_post_statuses'], 10, 3);
        add_filter('fluentcrm_ajax_options_post_types', [$this, 'get_post_types'], 10, 3);
        add_filter('fluentcrm_ajax_options_woo_payment_methods', [$this, 'get_woo_payment_methods'], 10, 3);
        add_filter('fluentcrm_ajax_options_woo_shipping_methods', [$this, 'get_woo_shipping_methods'], 10, 3);
        add_filter('fluentcrm_ajax_options_available_roles', [$this, 'get_available_roles'], 10, 3);
        add_filter('fluentcrm_ajax_options_cct_list', [$this, 'get_cct_list'], 10, 3);
    }

    public function get_jfb_forms($options, $search, $includedIds)
    {
        $forms = get_posts([
            'post_type' => 'jet-form-builder',
            'numberposts' => -1,
            's' => $search
        ]);

        foreach ($forms as $form) {
            $options[] = [
                'id'    => $form->ID,
                'title' => $form->post_title
            ];
        }

        return $options;
    }

    public function get_post_statuses($options, $search, $includedIds)
    {
        $default_statuses = ['publish', 'pending', 'draft', 'private', 'trash'];

        $statuses = get_post_stati([], 'objects');

        foreach ($statuses as $status) {
            if (in_array($status->name, $default_statuses)) {
                $options[] = [
                    'id'    => $status->name,
                    'title' => $status->label
                ];
            }
        }

        return $options;
    }

    public function get_available_roles($options, $search, $includedIds)
    {
        global $wp_roles;
        $roles = $wp_roles->roles;

        foreach ($roles as $role_key => $role_data) {
            if (stripos($role_data['name'], $search) !== false || empty($search)) {
                $options[] = [
                    'id'    => $role_key,
                    'title' => $role_data['name']
                ];
            }
        }

        return $options;
    }

    public function get_post_types($options, $search, $includedIds)
    {
        $post_types = get_post_types(['public' => true], 'objects');

        foreach ($post_types as $post_type) {
            if (strpos($post_type->name, $search) !== false || empty($search)) {
                $options[] = [
                    'id'    => $post_type->name,
                    'title' => $post_type->label
                ];
            }
        }

        return $options;
    }

    public function get_woo_payment_methods($options, $search, $includedIds)
    {
        $payment_gateways = WC()->payment_gateways->payment_gateways();

        foreach ($payment_gateways as $gateway) {
            if ($gateway->enabled === 'yes') {
                $options[] = [
                    'id'    => $gateway->id,
                    'title' => $gateway->title
                ];
            }
        }

        return $options;
    }

    public function get_woo_shipping_methods($options, $search, $includedIds)
    {
        $shipping_zones = \WC_Shipping_Zones::get_zones();
        $shipping_zones[] = ['id' => 0]; 

        foreach ($shipping_zones as $zone) {
            $zone_id = $zone['id'];
            $shipping_methods = \WC_Shipping_Zones::get_zone($zone_id)->get_shipping_methods(true);
            foreach ($shipping_methods as $method) {
                $options[] = [
                    'id'    => $method->id . ':' . $method->instance_id,
                    'title' => $method->title
                ];
            }
        }

        return $options;
    }    
}
