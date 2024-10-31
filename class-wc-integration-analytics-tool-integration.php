<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * OneSight Analytics tool integration.
 *
 * @package   OneSight Analytics tool integration
 * @category Integration
 * @author   OneSight Studio.
 */
if ( ! class_exists( 'WC_Analytics_tool_Integration' ) ) :
    class WC_Analytics_tool_Integration extends WC_Integration {
        /**
         * Init and hook in the integration.
         */
        public function __construct() {
            global $woocommerce;
            $this->id                 = 'onesight-analytics-tool-integration';
            $this->method_title       = __( 'OneSight analytics tool integration');
            $this->method_description = __( 'OneSight analytics tool integration for Woocommerce.');
            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();
            // Define user set variables.
            $this->api_key          = $this->get_option( 'api_key' );
            $this->encryption_key   = $this->get_option( 'encryption_key' );
            // Actions.
            add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );
        }
        /**
         * Initialize integration settings form fields.
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'api_key' => array(
                    'title'             => __( 'API Secret Key'),
                    'type'              => 'password',
                    'description'       => __( 'Enter your API secret key here'),
                    'desc_tip'          => true,
                    'default'           => '',
                ),
                'encryption_key' => array(
                    'title'             => __( 'Encryption Key'),
                    'type'              => 'password',
                    'description'       => __( 'Enter your encryption key here'),
                    'desc_tip'          => true,
                    'default'           => '',
                ),
            );
        }
    }
endif;
