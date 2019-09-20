<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
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
use Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction;

class PaymentSdkConfigFactoryUTest extends \PHPUnit_Framework_TestCase
{
    const GET_VALUE = 'getValue';
    const BASE_URL = 'http://base.url';
    const WIRECARD_EE_MODULE_NAME = 'Wirecard_ElasticEngine';
    const WIRECARD_EE_VERSION = '2.2.0';
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
                'sepadirectdebit' => $methodConfigSepa,
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
        $configFromFactory = $this->configFactory->create(SepaDirectDebitTransaction::NAME);
        $this->assertInstanceOf(Config::class, $configFromFactory);

        $sepaConfig = new SepaConfig(
            SepaDirectDebitTransaction::NAME,
            'account_id_123',
            'secret_key'
        );
        $sepaConfig->setCreditorId('1234');
        $this->assertEquals($sepaConfig, $configFromFactory->get(SepaDirectDebitTransaction::NAME));
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
            'shop-system-name' => 'Magento+Community Edition',
            'shop-system-version' => self::MAGENTO_VERSION,
            'plugin-name' => 'magento2-ee+Wirecard',
            'plugin-version' => self::WIRECARD_EE_VERSION
        ]]);
    }
}
