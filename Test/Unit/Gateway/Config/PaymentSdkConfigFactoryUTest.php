<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
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

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Config;

use Magento\Framework\App\ProductMetadata;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Payment\Gateway\ConfigFactoryInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Wirecard\ElasticEngine\Gateway\Config\PaymentSdkConfigFactory;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\CreditCardConfig;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Config\SepaConfig;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\Transaction\IdealTransaction;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\Transaction\SepaTransaction;

class PaymentSdkConfigFactoryUTest extends \PHPUnit_Framework_TestCase
{
    const GET_VALUE = 'getValue';
    const BASE_URL = 'http://base.url';
    const WIRECARD_EE_MODULE_NAME = 'Wirecard_ElasticEngine';
    const WIRECARD_EE_VERSION = '2.0.0';
    const MAGENTO_VERSION = '2.1.0';

    /**
     * @var ConfigInterface
     */
    private $eeConfig;

    /**
     * @var ProductMetadata
     */
    private $productMetadata;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var ConfigFactoryInterface
     */
    private $configFactory;

    public function setUp()
    {
        $this->eeConfig = $this->getMock(ConfigInterface::class);
        $this->eeConfig->method(self::GET_VALUE)->withConsecutive(
            ['base_url'],
            ['http_user'],
            ['http_pass']
        )->willReturnOnConsecutiveCalls(self::BASE_URL, 'user', 'pass');

        $methodConfigPayPal = $this->getMock(ConfigInterface::class);
        $methodConfigPayPal->method(self::GET_VALUE)->withConsecutive(
            ['base_url'],
            ['http_user'],
            ['http_pass'],
            ['merchant_account_id'],
            ['secret']
        )->willReturnOnConsecutiveCalls(self::BASE_URL, 'user', 'pass', 'account_id_123', 'secret_key');

        $methodConfigCreditCard = $this->getMock(ConfigInterface::class);
        $methodConfigCreditCard->method(self::GET_VALUE)->willReturnCallback(function ($key) {
            $map = [
                'base_url' => self::BASE_URL,
                'http_user' => 'user',
                'http_pass' => 'pass',
                'merchant_account_id' => 'account_ssl',
                'secret' => 'secret_ssl',
                'three_d_merchant_account_id' => 'account_id_three',
                'three_d_secret' => 'secret_three',
                'ssl_max_limit' => 100.0,
                'three_d_min_limit' => 50.0,
                'default_currency' => 'EUR'
            ];

            return $map[$key];
        });

        $methodConfigSepa = $this->getMock(ConfigInterface::class);
        $methodConfigSepa->method(self::GET_VALUE)->willReturnCallback(function ($key) {
            $map = [
                'base_url' => self::BASE_URL,
                'http_user' => 'user',
                'http_pass' => 'pass',
                'merchant_account_id' => 'account_id_123',
                'secret' => 'secret_key',
                'creditor_id' => '1234'
            ];

            return $map[$key];
        });

        $methodConfigIdeal = $this->getMock(ConfigInterface::class);
        $methodConfigIdeal->method(self::GET_VALUE)->withConsecutive(
            ['base_url'],
            ['http_user'],
            ['http_pass'],
            ['merchant_account_id'],
            ['secret']
        )->willReturnOnConsecutiveCalls(self::BASE_URL, 'user', 'pass', 'account_id_123', 'secret_key');

        $this->productMetadata = $this->getMockBuilder(ProductMetadata::class)
            ->disableOriginalConstructor()->getMock();
        $this->productMetadata->method('getName')->willReturn('Magento');
        $this->productMetadata->method('getEdition')->willReturn('Community');
        $this->productMetadata->method('getVersion')->willReturn(self::MAGENTO_VERSION);

        $this->moduleList = $this->getMock(ModuleListInterface::class);
        $this->moduleList->method('getOne')
            ->with(self::WIRECARD_EE_MODULE_NAME)
            ->willReturn(['setup_version' => self::WIRECARD_EE_VERSION]);

        $this->configFactory = new PaymentSdkConfigFactory(
            $this->eeConfig,
            [
                'paypal' => $methodConfigPayPal,
                'creditcard' => $methodConfigCreditCard,
                'sepa' => $methodConfigSepa,
                'ideal' => $methodConfigIdeal
            ],
            $this->productMetadata,
            $this->moduleList
        );
    }

    public function testCreateReturnsConfig()
    {
        $configFromFactory = $this->configFactory->create();
        $this->assertInstanceOf(Config::class, $configFromFactory);
    }

    public function testCreateAddsPayPal()
    {
        /** @var $configFromFactory Config */
        $configFromFactory = $this->configFactory->create('paypal');
        $this->assertInstanceOf(Config::class, $configFromFactory);

        $paypalConfig = new PaymentMethodConfig(
            PayPalTransaction::NAME,
            'account_id_123',
            'secret_key'
        );
        $this->assertEquals($paypalConfig, $configFromFactory->get(PayPalTransaction::NAME));
    }

    public function testCreateAddsCreditCard()
    {
        /** @var $configFromFactory Config */
        $configFromFactory = $this->configFactory->create(CreditCardTransaction::NAME);
        $this->assertInstanceOf(Config::class, $configFromFactory);

        $creditCardConfig = new CreditCardConfig(
            'account_ssl',
            'secret_ssl'
        );
        $creditCardConfig->addSslMaxLimit(new Amount(100.0, 'EUR'));
        $creditCardConfig->addThreeDMinLimit(new Amount(50.0, 'EUR'));
        $creditCardConfig->setThreeDCredentials('account_id_three', 'secret_three');
        $this->assertEquals($creditCardConfig, $configFromFactory->get(CreditCardTransaction::NAME));
    }

    public function testCreateAddsSepa()
    {
        /** @var $configFromFactory Config */
        $configFromFactory = $this->configFactory->create(SepaTransaction::NAME);
        $this->assertInstanceOf(Config::class, $configFromFactory);

        $sepaConfig = new SepaConfig(
            'account_id_123',
            'secret_key'
        );
        $sepaConfig->setCreditorId('1234');
        $this->assertEquals($sepaConfig, $configFromFactory->get(SepaTransaction::NAME));
    }

    public function testCreateAddsIdeal()
    {
        /** @var $configFromFactory Config */
        $configFromFactory = $this->configFactory->create(IdealTransaction::NAME);
        $this->assertInstanceOf(Config::class, $configFromFactory);

        $idealConfig = new PaymentMethodConfig(
            IdealTransaction::NAME,
            'account_id_123',
            'secret_key'
        );
        $this->assertEquals($idealConfig, $configFromFactory->get(IdealTransaction::NAME));
    }

    public function testCreateSetsShopInfo()
    {
        /** @var $configFromFactory Config */
        $configFromFactory = $this->configFactory->create('paypal');

        $this->assertEquals($configFromFactory->getShopHeader(), ['headers' => [
            'shop-system-name' => 'Magento Community Edition',
            'shop-system-version' => '2.1.0',
            'plugin-name' => 'Wirecard_ElasticEngine',
            'plugin-version' => '2.0.0'
        ]]);
    }
}
