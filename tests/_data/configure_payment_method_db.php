<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 *
 * @author Wirecard AG
 * @copyright Wirecard AG
 * @license GPLv3
 */

define('GATEWAY_CONFIG_PATH', 'gateway_configs');

$gateway = getenv('GATEWAY');
if (!$gateway) {
    $gateway = 'API-TEST';
}

$defaultConfig = [
    'creditcard' => [
        'base_url' => 'https://api-test.wirecard.com',
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

if (count($argv) < 2) {
    $supportedPaymentMethods = implode("\n  ", array_keys($GLOBALS['defaultConfig']));
    echo <<<END_USAGE
Usage: php configure_payment_method_db.php <paymentmethod>

Supported payment methods:
  $supportedPaymentMethods


END_USAGE;
    exit(1);
}
$paymentMethod = trim($argv[1]);

$dbConfig = buildConfigByPaymentMethod($paymentMethod, $gateway);
if (empty($dbConfig)) {
    echo "Payment method $paymentMethod is not supported\n";
    exit(1);
}

updateMagento2EeDbConfig($dbConfig, $paymentMethod);

/**
 * Method buildConfigByPaymentMethod
 * @param string $paymentMethod
 * @param string $gateway
 * @return array
 *
 * @since   1.4.1
 */

function buildConfigByPaymentMethod($paymentMethod, $gateway)
{
    if (!array_key_exists($paymentMethod, $GLOBALS['defaultConfig'])) {
        return null;
    }
    $config = $GLOBALS['defaultConfig'][$paymentMethod];

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
    echo 'Configuring ' . $payment_method . " payment method in the shop system \n";
    $dbHost = 'db';
    $dbName = getenv('MYSQL_DATABASE');
    $dbUser = getenv('MYSQL_USER');
    $dbPass = getenv('MYSQL_PASSWORD');

    $tableName = 'core_config_data';

    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($mysqli->connect_errno) {
        echo "Can't connect DB $dbName on host $dbHost as user $dbUser \n";
        return false;
    }

    $gateway = getenv('GATEWAY');
    if ('API-WDCEE-TEST' === $gateway) {
        $mysqli->query("INSERT INTO $tableName (path, value) VALUES ('wirecard_elasticengine/credentials/base_url', 'https://api-wdcee-test.wirecard.com')");
        $mysqli->query("INSERT INTO $tableName (path, value) VALUES ('wirecard_elasticengine/credentials/http_user', 'pink-test')");
        $mysqli->query("INSERT INTO $tableName (path, value) VALUES ('wirecard_elasticengine/credentials/http_pass', '8f5y2h0s')");
    } elseif ('NOVA' === $gateway) {
        $mysqli->query("INSERT INTO $tableName (path, value) VALUES ('wirecard_elasticengine/credentials/base_url', 'https://payments-test.wirecard.com')");
        $mysqli->query("INSERT INTO $tableName (path, value) VALUES ('wirecard_elasticengine/credentials/http_user', 'NovaTeam')");
        $mysqli->query("INSERT INTO $tableName (path, value) VALUES ('wirecard_elasticengine/credentials/http_pass', 'kCopTTMkpw')");
    }

    foreach ($db_config as $name => $value) {
        $path = sprintf("payment/wirecard_elasticengine_creditcard/%s", $name);

        $stmt = $mysqli->prepare("INSERT INTO $tableName (path, value) VALUES (?, ?)");
        $stmt->bind_param("ss", $path, $value);

        $stmt->execute();
    }

    echo "New Database rows!\n";
    $stmtInsert = "SELECT * FROM $tableName";
    $result = $mysqli->query($stmtInsert);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo $row['path'] . " " . $row['value'] . "\n";
        }
    }
    return true;
}
