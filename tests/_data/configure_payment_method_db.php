<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

define('GATEWAY_CONFIG_PATH', 'gateway_configs');

$gateway = getenv('GATEWAY');
if (!$gateway) {
    $gateway = 'API-TEST';
}

$defaultConfig = [
    'creditcard' => [
        'base_url' => 'https://api-test.wirecard.com',
        'wpp_url'  => 'https://wpp-test.wirecard.com',
        'http_user' => '70000-APITEST-AP',
        'http_pass' => 'qD2wzQ_hrc!8',
        'three_d_merchant_account_id' => '508b8896-b37d-4614-845c-26bf8bf2c948',
        'three_d_secret' => 'dbc5a498-9a66-43b9-bf1d-a618dd399684',
        'merchant_account_id' => '53f2895a-e4de-4e82-a813-0d87a10e55e6',
        'secret' => 'dbc5a498-9a66-43b9-bf1d-a618dd399684',

        'active' => '1',
        'title' => 'Wirecard Credit Card',
        'send_additional' => '1',
        'ssl_max_limit' => '100.0',
        'three_d_min_limit' => '50.0',
        'default_currency' => 'EUR',
        'payment_action' => 'authorize',
        'sort_order' => '1'
    ]
];
$supportedPaymentActionsPerPaymentMethod = [
    'creditcard' => ['authorize', 'authorize_capture']
];

if (count($argv) < 3) {
    $supportedPaymentMethods = implode("\n  ", array_keys($GLOBALS['defaultConfig']));
    $supportedPaymentActions = '';
    foreach ($GLOBALS['defaultConfig'] as $key => $value) {
        $supportedPaymentActions .= $supportedPaymentActions . "\n  "
            . $key . ': ' . implode(",  ", $supportedPaymentActionsPerPaymentMethod[$key]);
    }

    echo <<<END_USAGE
Usage: php configure_payment_method_db.php <paymentmethod>

Supported payment methods:
  $supportedPaymentMethods
Supported operations:
    $supportedPaymentActions
 


END_USAGE;
    exit(1);
}
$paymentMethod = trim($argv[1]);
$paymentAction = trim($argv[2]);

$dbConfig = buildConfigByPaymentMethod($paymentMethod, $paymentAction, $gateway);
if (empty($dbConfig)) {
    echo "Payment method $paymentMethod is not supported\n";
    exit(1);
}

if (!in_array($paymentAction, $supportedPaymentActionsPerPaymentMethod[$paymentMethod])) {
    echo "Payment action $paymentAction is not supported\n";
    exit(1);
}

updateMagento2EeDbConfig($dbConfig, $paymentMethod);

/**
 * Method buildConfigByPaymentMethod
 * @param string $paymentMethod
 * @param string $paymentAction
 * @param string $gateway
 * @return array
 *
 * @since   1.4.1
 */

function buildConfigByPaymentMethod($paymentMethod, $paymentAction, $gateway)
{
    if (!array_key_exists($paymentMethod, $GLOBALS['defaultConfig'])) {
        return null;
    }
    $config = $GLOBALS['defaultConfig'][$paymentMethod];
    $config['payment_action'] = $paymentAction;
    $jsonFile = GATEWAY_CONFIG_PATH . DIRECTORY_SEPARATOR . $paymentMethod . '.json';
    if (file_exists($jsonFile)) {
        $jsonData = json_decode(file_get_contents($jsonFile));
        if (!empty($jsonData) && !empty($jsonData->$gateway)) {
            foreach (get_object_vars($jsonData->$gateway) as $key => $data) {
                // only replace values from json if the key is defined in defaultDbValues
                if (array_key_exists($key, $config)) {
                    $config[$key] = $data;
                }
            }
        }
    }
    $config['payment_action'] = $paymentAction;
    return $config;
}

/**
 * Method updateMagento2EeDbConfig
 * @param array $db_config
 * @param string $payment_method
 * @return boolean
 *
 * @since   1.4.1
 */
function updateMagento2EeDbConfig($db_config, $payment_method)
{
    echo '\nConfiguring ' . $payment_method . " payment method in the shop system\n";
    $dbHost = 'db';
    $dbName = getenv('MYSQL_DATABASE');
    $dbUser = getenv('MYSQL_USER');
    $dbPass = getenv('MYSQL_PASSWORD');
    $dbPort = getenv('MYSQL_PORT_IN');

    $tableName = 'core_config_data';

    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
    if ($mysqli->connect_errno) {
        echo "Can't connect DB $dbName on host $dbHost as user $dbUser \n";
        return false;
    }

    $paymentMethodActivation = array_slice($db_config, 0, 3);
    foreach ($paymentMethodActivation as $name => $value) {
        $path = sprintf("wirecard_elasticengine/credentials/%s", $name);

        $stmt = $mysqli->prepare("INSERT INTO $tableName (path, value) VALUES (?, ?)");
        $stmt->bind_param("ss", $path, $value);
        $stmt->execute();
    }

    foreach ($db_config as $name => $value) {
        $path = sprintf("payment/wirecard_elasticengine_creditcard/%s", $name);

        $stmt = $mysqli->prepare("REPLACE INTO $tableName (path, value) VALUES (?, ?)");
        $stmt->bind_param("ss", $path, $value);
        $stmt->execute();
    }
    return true;
}
