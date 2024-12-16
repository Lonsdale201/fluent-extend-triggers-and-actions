<?php
/**
 * Plugin Name: Fluent Extend Triggers and Actions
 * Description: Extra triggers, actions and others for JetFormBuilder, Woo, user roles, Jetreviews
 * Version: 1.4
 * Author: Soczó Kristóf
 * Author URI: https://github.com/Lonsdale201/fluent-extend-triggers-and-actions
 * Text Domain: hw-fluent-extendtriggers
 * Requires Plugins: fluent-crm
 */

namespace HelloWP\FluentExtendTriggers;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
require dirname(__FILE__) . '/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5p0\PucFactory;

final class HW_Fluent_Extend_Main {

    const MINIMUM_FLUENTCRM_VERSION = '2.8.0';
    const MINIMUM_FLUENTCAMPAIGN_VERSION = '2.8.0';
    const MINIMUM_WORDPRESS_VERSION = '6.0';
    const MINIMUM_PHP_VERSION = '7.4';

    private static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('plugins_loaded', [$this, 'init_on_plugins_loaded']);
        add_action('init', [$this, 'load_plugin_textdomain']);
    }

    public function load_plugin_textdomain() {
        load_plugin_textdomain('hw-fluent-extendtriggers', false, basename(dirname(__FILE__)) . '/languages');
    }

    public function init_on_plugins_loaded() {
        if (!$this->is_compatible()) {
            return;
        }

        new \HelloWP\FluentExtendTriggers\Includes\TriggerManager();
        new \HelloWP\FluentExtendTriggers\Includes\CustomControllers();
        new \HelloWP\FluentExtendTriggers\Includes\ActionManager();

        // smartcodes
        new \HelloWP\FluentExtendTriggers\SmartCodes\PostSmartCodes();
        if (class_exists('Jet_Reviews')) {
            new \HelloWP\FluentExtendTriggers\SmartCodes\JetRewSmartCodes();
        }

        if (class_exists('Jet_Engine')) {
            new \HelloWP\FluentExtendTriggers\JEModules\MacrosManager();
        }

        $myUpdateChecker = PucFactory::buildUpdateChecker(
            'https://plugin-uodater.alex.hellodevs.dev/plugins/hw-fluent-extendtriggers.json',
            __FILE__,
            'hw-fluent-extendtriggers'
        );
    }


    public function is_compatible() {
        $compatible = true;
        if (!defined('FLUENTCRM') || version_compare(FLUENTCRM_PLUGIN_VERSION, self::MINIMUM_FLUENTCRM_VERSION, '<')) {
            $compatible = false;
            add_action('admin_notices', [$this, 'admin_notice_minimum_fluentcrm_version']);
        }

        if (version_compare(get_bloginfo('version'), self::MINIMUM_WORDPRESS_VERSION, '<')) {
            $compatible = false;
            add_action('admin_notices', [$this, 'admin_notice_minimum_wordpress_version']);
        }

        if (version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '<')) {
            $compatible = false;
            add_action('admin_notices', [$this, 'admin_notice_minimum_php_version']);
        }

        return $compatible;
    }

    public function admin_notice_minimum_fluentcrm_version() {
        if (!current_user_can('manage_options')) {
            return;
        }
        echo '<div class="notice notice-warning is-dismissible"><p>';
        echo sprintf(__('Fluent Extend Triggers and Actions requires FluentCRM version %s or greater. Please update FluentCRM to use this plugin.', 'hw-fluent-extendtriggers'), self::MINIMUM_FLUENTCRM_VERSION);
        echo '</p></div>';
    }

    public function admin_notice_minimum_wordpress_version() {
        if (!current_user_can('manage_options')) {
            return;
        }
        echo '<div class="notice notice-warning is-dismissible"><p>';
        echo sprintf(__('Fluent Extend Triggers and Actions requires WordPress version %s or greater. Please update WordPress to use this plugin.', 'hw-fluent-extendtriggers'), self::MINIMUM_WORDPRESS_VERSION);
        echo '</p></div>';
    }

    public function admin_notice_minimum_php_version() {
        if (!current_user_can('manage_options')) {
            return;
        }
        echo '<div class="notice notice-warning is-dismissible"><p>';
        echo sprintf(__('Fluent Extend Triggers and Actions requires PHP version %s vagy greater. Please update PHP to use this plugin.', 'hw-fluent-extendtriggers'), self::MINIMUM_PHP_VERSION);
        echo '</p></div>';
    }
}

HW_Fluent_Extend_Main::instance();
