<?php
/*
Plugin Name: Store Sync for WooCommerce
Plugin URI: https://wordpress.org/plugins/woo-store-sync
Description: Synchronize one WooCommerce store with another
Version: 1.0.1
Author: Moises Heberle
Author URI: https://pluggablesoft.com/contact
Text Domain: woo-store-sync
Domain Path: /i18n/languages/
WC requires at least: 3.2
WC tested up to: 4.5.1
*/

if ( ! defined( 'ABSPATH' ) ) exit;

defined('WSS_BASE_FILE') || define('WSS_BASE_FILE', __FILE__);
defined('WSS_LITE_INSTALLED') || define('WSS_LITE_INSTALLED', true);
defined('WSS_PLUGIN') || define('WSS_PLUGIN', plugin_basename( __FILE__));
defined('WSS_PREFIX') || define('WSS_PREFIX', 'wss');
defined('WSS_MENU_SLUG') || define('WSS_MENU_SLUG', 'wstoresync');
defined('WSS_DB_VERSION') || define('WSS_DB_VERSION', 8);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/wpmc/loader.php';
require_once __DIR__ . '/includes/WSS_Client.php';
require_once __DIR__ . '/includes/WSS_Logger.php';
require_once __DIR__ . '/includes/WSS_Settings.php';
require_once __DIR__ . '/includes/WSS_Scheduler.php';
require_once __DIR__ . '/includes/WSS_Synchronization.php';

if ( !class_exists('WStoreSync') ) {
    class WStoreSync {
        public function initHooks() {
            add_filter('init', array($this, 'pluginInit'));
            add_filter('wpmc_run_create_tables', array($this, 'checkMigrationRun'));
            add_action('admin_menu', array($this, 'createAdminMenus'));
        }

        public function config($name, $default = null) {
            return apply_filters('mh_wss_setting_value', $name);
        }

        public function pluginInit(){
            // load Common library
            require_once __DIR__ . '/common/MHCommon.php';
            MHCommon::initializeV2(
                'woo-store-sync',
                WSS_PREFIX,
                WSS_BASE_FILE,
                __('WooCommerce Store Sync', 'woo-store-sync')
            );

            $settings = new WSS_Settings();
            $settings->initHooks();

            $sync = new WSS_Synchronization();
            $sync->initHooks();

            if ( wstoresync()->config('track_logs') == 'yes' ) {
                $logger = new WSS_Logger();
                $logger->initHooks();
            }
        }

        /**
         * Check if migration run is needed
         */
        public function checkMigrationRun(){
            $currVersion = WSS_DB_VERSION;
            $dbVersion = get_option('wssync_version', 1);
            
            if ( $dbVersion != $currVersion ) {
                update_option('wssync_version', $currVersion);
                return true;
            }

            return false;
        }

        public function createAdminMenus() {
            $prefix = WSS_PREFIX;

            add_filter("mh_{$prefix}_parent_menu", function(){
                return WSS_MENU_SLUG;
            });
            
            add_filter("mh_{$prefix}_menu_slug", function(){
                return WSS_MENU_SLUG;
            });
            
            add_filter("mh_{$prefix}_menu_title", function(){
                return __('Settings', 'woo-store-sync');
            });
        
            // register main menu
            add_menu_page(
                __('Woo Store Sync', 'woo-store-sync'),
                __('Woo Store Sync', 'woo-store-sync'),
                'manage_woocommerce',
                WSS_MENU_SLUG,
                function(){},
                'dashicons-admin-multisite',
                56
            );
        }
    }
}

if ( !function_exists('wstoresync') ) {
    /**
     * @return WStoreSync
     */
    function wstoresync() {
        static $instance = null;

        if ( is_null($instance) ) {
            $instance = new WStoreSync();
        }

        return $instance;
    }

    wstoresync()->initHooks();
}