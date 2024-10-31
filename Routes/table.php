<?php

const ALLOWED_TABLE_NAMES = [
    'wc_customer_lookup',
    'posts',
    'postmeta',
    'wc_product_meta_lookup',
    'term_relationships',
    'term_taxonomy',
    'terms',
    'termmeta',
    'wc_order_stats',
    'wc_order_coupon_lookup',
    'woocommerce_order_items',
    'woocommerce_order_itemmeta',
];

const ALLOWED_COLUMN_NAMES = [
    'customer_id',
    'user_id',
    'username',
    'first_name',
    'last_name',
    'email',
    'date_last_active',
    'date_registered',
    'country',
    'postcode',
    'city',
    'state',
    'ID',
    'id',
    'post_author',
    'post_date',
    'post_date_gmt',
    'post_content',
    'post_title',
    'post_excerpt',
    'post_status',
    'comment_status',
    'ping_status',
    'post_password',
    'post_name',
    'to_ping',
    'pinged',
    'post_modified',
    'post_modified_gmt',
    'post_content_filtered',
    'post_parent',
    'guid',
    'menu_order',
    'post_type',
    'post_mime_type',
    'comment_count',
    'meta_id',
    'post_id',
    'meta_key',
    'meta_value',
    'product_id',
    'sku',
    'virtual',
    'downloadable',
    'min_price',
    'max_price',
    'onsale',
    'stock_quantity',
    'stock_status',
    'rating_count',
    'average_rating',
    'total_sales',
    'tax_status',
    'tax_class',
    'object_id',
    'term_taxonomy_id',
    'term_order',
    'term_id',
    'taxonomy',
    'description',
    'parent',
    'count',
    'name',
    'slug',
    'term_group',
    'order_id',
    'parent_id',
    'date_created',
    'date_created_gmt',
    'num_items_sold',
    'tax_total',
    'shipping_total',
    'net_total',
    'returning_customer',
    'status',
    'coupon_id',
    'discount_amount',
    'order_item_id',
    'order_item_name',
    'order_item_type',
];

// Registering the route
register_rest_route(ONESIGHT_ANALYTICS_TOOL_NAMESPACE, '/' . 'table', array(
    array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'table_results',
        'permission_callback' => array($this, 'checkApiKey'),
        'args' => array(),
    ),
));

// Getting results
function table_results($request)
{
    global $wpdb;
    $prefix = $wpdb->prefix;
    if (! (
        null !== $request->get_param('table') &&
        null !== $request->get_param('columns') &&
        null !== $request->get_param('limit') &&
        null !== $request->get_param('offset'))
    ) {
        return new WP_REST_Response([
            'error' => 'Missing input(s), must send `table`, `columns`, `limit` and `offset` params.'
        ], 422);
    }

    if (! checkTableName($request)) {
        return new WP_REST_Response(['error' => 'Invalid table name'], 422);
    }

    if (! checkColumnNames($request)) {
        return new WP_REST_Response(['error' => 'Invalid column names'], 422);
    }

    $table = $prefix . $request->get_param('table');
    $columns = $request->get_param('columns');
    $limit = 'LIMIT ' . esc_sql($request->get_param('limit'));
    $offset = 'OFFSET ' . esc_sql($request->get_param('offset'));
    $updatedAfter = $request->get_param('updated_after');

    $query = generateQuery($table, $columns, $limit, $offset, $updatedAfter, $prefix);

    $results = $wpdb->get_results($query);

    $results = encrypt(base64_encode(gzcompress(json_encode($results), 9)));

    return new WP_REST_Response($results, 200);
}

function encrypt($input)
{
    $options = get_option('woocommerce_onesight-analytics-tool-integration_settings');
    $encryptionKey = !empty($options['encryption_key']) ? $options['encryption_key'] : null;
    if ($encryptionKey) {
        return encryptString($input, $encryptionKey);
    }

    return $input;
}

function encryptString($in, $key)
{
    $cipher_algo = 'aes-256-gcm';
    $hash_algo = 'sha256';
    $iv_num_bytes = openssl_cipher_iv_length($cipher_algo);
    // Build an initialisation vector
    $iv = openssl_random_pseudo_bytes($iv_num_bytes, $isStrongCrypto);

    // Hash the key
    $keyhash = openssl_digest($key, $hash_algo, true);

    // and encrypt
    $opts =  OPENSSL_RAW_DATA;
    $encrypted = openssl_encrypt($in, $cipher_algo, $keyhash, $opts, $iv, $tag);

    if ($encrypted === false) {
        throw new Exception('Encryption failed: '.openssl_error_string());
    }

    // The result comprises the tag, IV and encrypted data
    $res = $tag . $iv . $encrypted;

    // and format the result.
    $res = base64_encode($res);

    return $res;
}

function generateQuery($table, $columns, $limit, $offset, $updatedAfter, $prefix)
{
    if (str_contains($table, 'posts')) {
        return generatePostsQuery($table, $columns, $limit, $offset, $updatedAfter);
    }

    if (str_contains($table, 'postmeta') ||
        str_contains($table, 'wc_order_stats') ||
        str_contains($table, 'woocommerce_order_items')) {
        return generatePostsJoinQuery($table, $columns, $limit, $offset, $updatedAfter, $prefix);
    }

    if (str_contains($table, 'woocommerce_order_itemmeta')) {
        return generateOrderItemsmetaQuery($table, $columns, $limit, $offset, $updatedAfter, $prefix);
    }

    return generateStandardQuery($table, $columns, $limit, $offset);
}

function generateStandardQuery($table, $columns, $limit, $offset)
{
    $columns = implode(',', $columns);

    return
        <<<EOT
        SELECT
            $columns
        FROM 
            $table
        $limit $offset ;
        EOT;
}

function generatePostsQuery($table, $columns, $limit, $offset, $updatedAfter)
{
    $columns = implode(',', $columns);

    $where = $updatedAfter ? "post_modified_gmt > '$updatedAfter'" : "1";

    return
        <<<EOT
        SELECT
            $columns
        FROM 
            $table
        WHERE $where
        $limit $offset ;
        EOT;
}

function generatePostsJoinQuery($table, $columns, $limit, $offset, $updatedAfter, $prefix)
{
    array_walk($columns, static function (&$value) {
        $value = 'sel.' . $value;
    });
    $columns = implode(',', $columns);
    $postsTable = $prefix . 'posts';
    $joinKey = str_contains($table, 'postmeta') ? 'post_id' : 'order_id';
    $where = $updatedAfter ? "post_modified_gmt > '$updatedAfter'" : "1";

    return
        <<<EOT
        SELECT
            $columns
        FROM 
            $table as sel
        JOIN
            $postsTable as posts
        ON
            sel.$joinKey = posts.ID
        WHERE $where
        $limit $offset ;
        EOT;
}

function generateOrderItemsmetaQuery($table, $columns, $limit, $offset, $updatedAfter, $prefix)
{
    array_walk($columns, static function (&$value) {
        $value = 'itemsmeta.' . $value;
    });
    $columns = implode(',', $columns);
    $orderItemsTable = $prefix . 'woocommerce_order_items';
    $postsTable = $prefix . 'posts';
    $where = $updatedAfter ? "post_modified_gmt > '$updatedAfter'" : "1";

    return
        <<<EOT
        SELECT
            $columns
        FROM 
            $table as itemsmeta
        JOIN
            $orderItemsTable as items
        ON
            itemsmeta.order_item_id = items.order_item_id
        JOIN
            $postsTable as posts
        ON
            items.order_id = posts.ID
        WHERE $where
        $limit $offset ;
        EOT;
}

function checkTableName($request): bool
{
    if (in_array($request->get_param('table'), ALLOWED_TABLE_NAMES)) {
        return true;
    }

    return false;
}

function checkColumnNames($request): bool
{
    $eligibleColumns = array_intersect(ALLOWED_COLUMN_NAMES, $request->get_param('columns'));
    if (count($eligibleColumns) == count($request->get_param('columns'))) {
        return true;
    }

    return false;
}
