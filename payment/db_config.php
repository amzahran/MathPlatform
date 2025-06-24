<?php
// إعدادات Ahli
define('AHLI_API_KEY', 'your_ahli_api_key_here');
define('AHLI_MERCHANT_ID', 'your_merchant_id');
define('AHLI_ENDPOINT', 'https://qnb-payment-gateway.com/api');

// إعدادات QNB
define('QNB_API_KEY', 'your_qnb_api_key_here');
define('QNB_MERCHANT_ID', 'your_merchant_id');
define('QNB_ENDPOINT', 'https://qnb-payment-gateway.com/api');

// إعدادات We
define('WE_APP_ID', 'your_we_app_id');
define('WE_SECRET_KEY', 'your_we_secret_key');
define('WE_ENDPOINT', 'https://we-payment-api.com/v1');

// إعدادات قاعدة البيانات
$dbConfig = [
    'server' => 'ahmedzahraniSQL',
    'database' => 'platform',
    'username' => 'sa',
    'password' => '123123'
];

function getDBConnection() {
    global $dbConfig;
    $conn = sqlsrv_connect(
        $dbConfig['server'],
        [
            "Database" => $dbConfig['database'],
            "Uid" => $dbConfig['username'],
            "PWD" => $dbConfig['password'],
            "CharacterSet" => "UTF-8",
            "ReturnDatesAsStrings" => true
        ]
    );
    if (!$conn) {
        throw new Exception("Database connection failed: " . print_r(sqlsrv_errors(), true));
    }
    return $conn;
}
?>