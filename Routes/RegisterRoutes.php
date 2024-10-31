<?php

class RegisterRoutes extends WP_REST_Controller {

    private $apiKey;

    CONST API_VERSION = 1;

    public function __construct()
    {
        $options = get_option('woocommerce_onesight-analytics-tool-integration_settings');
        $this->apiKey = !empty($options['api_key']) ? $options['api_key'] : null;
    }

    /**
     * Register the routes.
     */
    public function register_routes() {
        define('ONESIGHT_ANALYTICS_TOOL_NAMESPACE', 'onesight-analytics/v' . $this::API_VERSION);

        add_action( 'rest_api_init', function () {
            include_once 'config.php';
            include_once 'table.php';
        });
    }

    public function checkApiKey( $request ): bool
    {
        if (! $this->apiKey || $this->apiKey != $request->get_header('api-key')) {
            return false;
        }

        return true;
    }
}
