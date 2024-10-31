<?php

// Registering the route
register_rest_route(ONESIGHT_ANALYTICS_TOOL_NAMESPACE, '/' . 'config', array(
    array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'config',
        'permission_callback' => array($this, 'checkApiKey'),
        'args' => array(),
    ),
));

// Getting results
function config($request)
{
    global $wpdb;
    $optionsTable = $wpdb->prefix . 'options';

    $query = <<<EOT
            SELECT
                options.option_value
            FROM 
                $optionsTable as options
            WHERE
                option_name = 'woocommerce_currency';
            EOT;
    $currency = $wpdb->get_results($query)[0]->option_value;

    $query = <<<EOT
            SELECT
                options.option_value
            FROM 
                $optionsTable as options
            WHERE
                option_name = 'gmt_offset';
            EOT;
    $timezoneDiff = $wpdb->get_results($query)[0]->option_value;

    $results = [
        'currency' => $currency,
        'timezone_diff' => $timezoneDiff
    ];

    return new WP_REST_Response($results, 200);
}
