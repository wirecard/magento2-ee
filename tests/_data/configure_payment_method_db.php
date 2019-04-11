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
 */

// the path for different config files, each named as <paymentmethod>.json
define( 'GATEWAY_CONFIG_PATH', 'gateway_configs' );

$gateway = getenv( 'GATEWAY' );
if ( ! $gateway ) {
	$gateway = 'API-TEST';
}

// the default config defines valid keys for each payment method and is prefilled with API-TEST setup by default
$defaultConfig = [
	'creditcard' => [
		'base_url'            => 'https://api-test.wirecard.com',
		'http_user'           => '70000-APITEST-AP',
		'http_pass'           => 'qD2wzQ_hrc!8',
		'threed_maid'         => '508b8896-b37d-4614-845c-26bf8bf2c948',
		'threed_secret'       => 'dbc5a498-9a66-43b9-bf1d-a618dd399684',
		'merchant_account_id' => '53f2895a-e4de-4e82-a813-0d87a10e55e6',
		'secret'              => 'dbc5a498-9a66-43b9-bf1d-a618dd399684',
		'ssl_max_limit'       => 100,
		'three_d_min_limit'   => 50,

		'enabled'             => 'yes',
		'title'               => 'Wirecard Credit Card',
		'credentials'         => '',
		'test_button'         => 'Test',
		'advanced'            => '',
		'payment_action'      => 'pay',
		'descriptor'          => 'no',
		'send_additional'     => 'yes',
		'cc_vault_enabled'    => 'no',
	],
];

// main script - read payment method from command line, build the config and write it into database
if ( count( $argv ) < 2 ) {
	$supportedPaymentMethods = implode( "\n  ", array_keys( $GLOBALS['defaultConfig'] ) );
	echo <<<END_USAGE
Usage: php configure_payment_method_db.php <paymentmethod>

Supported payment methods:
  $supportedPaymentMethods


END_USAGE;
	exit( 1 );
}
$paymentMethod = trim( $argv[1] );

$dbConfig = buildConfigByPaymentMethod( $paymentMethod, $gateway );
if ( empty( $dbConfig ) ) {
	echo "Payment method $paymentMethod is not supported\n";
	exit( 1 );
}

updateMagento2EeDbConfig( $dbConfig, $paymentMethod );

/**
 * Method buildConfigByPaymentMethod
 * @param string $paymentMethod
 * @param string $gateway
 * @return array
 *
 * @since   1.4.4
 */

function buildConfigByPaymentMethod( $paymentMethod, $gateway ) {
	if ( ! array_key_exists( $paymentMethod, $GLOBALS['defaultConfig'] ) ) {
		return null;
	}
	$config = $GLOBALS['defaultConfig'][ $paymentMethod ];

	$jsonFile = GATEWAY_CONFIG_PATH . DIRECTORY_SEPARATOR . $paymentMethod . '.json';
	if ( file_exists( $jsonFile ) ) {
		$jsonData = json_decode( file_get_contents( $jsonFile ) );
		if ( ! empty( $jsonData ) && ! empty( $jsonData->$gateway ) ) {
			foreach ( get_object_vars( $jsonData->$gateway ) as $key => $data ) {
				// only replace values from json if the key is defined in defaultDbValues
				if ( array_key_exists( $key, $config ) ) {
					$config[ $key ] = $data;
				}
			}
		}
	}
	return $config;
}

/**
 * Method update_magento2_ee_db_config
 * @param array $db_config
 * @param string $payment_method
 * @return boolean
 *
 * @since   1.4.4
 */
function updateMagento2EeDbConfig( $db_config, $payment_method ) {
	echo 'Configuring ' . $payment_method . " payment method in the shop system \n";
	//DB setup
	$dbHost = 'db';
	$dbName = 'magento';
	$dbUser = 'magento';
	$dbPass = 'magento';
	$dbPort = '3306';

	$tableName            = 'core_config_data';
//	$creditCardSettingKey = 'woocommerce_wirecard_ee_' . $payment_method . '_settings';

//	$serializedConfig = serialize( $db_config );

	// create connection
	$mysqli = new mysqli( $dbHost, $dbUser, $dbPass, $dbName, $dbPort );
	if ( $mysqli->connect_errno ) {
		echo "Can't connect DB $dbName on host $dbHost as user $dbUser \n";
		return false;
	}

//	$stmtDelete = $mysqli->prepare( "DELETE FROM $tableName WHERE option_name = ?" );
//	$stmtDelete->bind_param( 's', $creditCardSettingKey );
//	$stmtDelete->execute();
//
//	$stmtInsert = $mysqli->prepare( "INSERT INTO $tableName (option_name, option_value, autoload) VALUES (?, ?, 'yes')" );
//	$stmtInsert->bind_param( 'ss', $creditCardSettingKey, $serializedConfig );
//	$stmtInsert->execute();

	return true;
}
