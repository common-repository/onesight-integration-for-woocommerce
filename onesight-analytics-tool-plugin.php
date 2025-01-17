<?php
include_once "Routes/RegisterRoutes.php";

/**
 * Plugin Name: OneSight Integration for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/onesight-integration-for-woocommerce
 * Description: To integrate with OneSight's analytics tool for Woocommerce
 * Author:  OneSight
 * Author URI: https://onesight.ai
 * Version: 1.1
 */

if ( ! class_exists( 'WC_analytics_tool_plugin' ) ) :
    class WC_analytics_tool_plugin {
        /**
         * Construct the plugin.
         */
        public function __construct() {
            add_action( 'plugins_loaded', array( $this, 'init' ) );
        }

        /**
         * Initialize the plugin.
         */
        public function init() {
            // Checks if WooCommerce is installed.
            if ( class_exists( 'WC_Integration' ) ) {
                // Include our integration class.
                include_once 'class-wc-integration-analytics-tool-integration.php';
                // Register the integration.
                add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
                // Set the plugin slug
                define( 'MY_PLUGIN_SLUG', 'wc-settings' );
                // Setting action for plugin
                add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'WC_analytics_tool_plugin_action_links' );

                // Register Routes
                $routesRegister = new RegisterRoutes();
                $routesRegister->register_routes();
            }
        }

        /**
         * Add a new integration to WooCommerce.
         */
        public function add_integration( $integrations ) {
            $integrations[] = 'WC_Analytics_tool_Integration';
            return $integrations;
        }
    }

    $WC_my_custom_plugin = new WC_analytics_tool_plugin( __FILE__ );

    function WC_analytics_tool_plugin_action_links( $links ) {

        $links[] = '<a href="'. menu_page_url( MY_PLUGIN_SLUG, false ) .'&tab=integration">Settings</a>';
        return $links;
    }
endif;
