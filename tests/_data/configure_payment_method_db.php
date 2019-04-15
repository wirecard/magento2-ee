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
        'ssl_max_limit' => 100,
        'three_d_min_limit' => 50,

        'enabled' => '1',
        'title' => 'Wirecard Credit Card',
        'credentials' => '',
        'test_button' => 'Test',
        'advanced' => '',
        'payment_action' => 'pay',
        'descriptor' => '0',
        'send_additional' => '1',
        'cc_vault_enabled' => '0',
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
    $dbName = 'magento';
    $dbUser = 'magento';
    $dbPass = 'magento';

    $tableName = 'core_config_data';

    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($mysqli->connect_errno) {
        echo "Can't connect DB $dbName on host $dbHost as user $dbUser \n";
        return false;
    } else {
        echo "Connection established successfully!";
    }

    foreach ($db_config as $name => $value) {
        $mysqli->query("UPDATE $tableName SET value = '1' WHERE path = 'payment/wirecard_elasticengine_creditcard/active'");
        if ('base_url' === $name) {
            $baseUrl = serialize($value);
            $mysqli->query("UPDATE $tableName SET value = '$baseUrl' WHERE path = 'payment/wirecard_elasticengine_creditcard/base_url'");
        }
        if ('http_user' === $name) {
            $httpUser = serialize($value);
            $mysqli->query("UPDATE $tableName SET value = '$httpUser' WHERE path = 'payment/wirecard_elasticengine_creditcard/http_user'");
        }
        if ('http_pass' === $name) {
            $httpPass = serialize($value);
            $mysqli->query("UPDATE $tableName SET value = '$httpPass' WHERE path = '	payment/wirecard_elasticengine_creditcard/http_pass'");
        }
        if ('three_d_merchant_account_id' === $name) {
            $threeDMerchantAccountId = serialize($value);
            $mysqli->query("UPDATE $tableName SET value = '$threeDMerchantAccountId' WHERE path = 'payment/wirecard_elasticengine_creditcard/three_d_merchant_account_id'");
        }
        if ('three_d_secret' === $name) {
            $threeDSecret = serialize($value);
            $mysqli->query("UPDATE $tableName SET value = '$threeDSecret' WHERE path = 'payment/wirecard_elasticengine_creditcard/three_d_secret'");
        }
        if ('merchant_account_id' === $name) {
            $merchantAccountId = serialize($value);
            $mysqli->query("UPDATE $tableName SET value = '$merchantAccountId' WHERE path = 'payment/wirecard_elasticengine_creditcard/merchant_account_id'");
        }
        if ('secret' === $name) {
            $secret = serialize($value);
            $mysqli->query("UPDATE $tableName SET value = '$secret' WHERE path = 'payment/wirecard_elasticengine_creditcard/secret'");
        }
    }
    return true;
}
