<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard Central Eastern Europe GmbH
 * (abbreviated to Wirecard CEE) and are explicitly not part of the Wirecard CEE range of
 * products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard CEE does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard CEE does not guarantee their full
 * functionality neither does Wirecard CEE assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard CEE does not guarantee the full functionality
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
use Magento\Payment\Gateway\ConfigInterface;
use Wirecard\ElasticEngine\Gateway\Config\PaymentSdkConfigFactory;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;

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
     * @var ConfigInterface
     */
    private $methodConfig;

    /**
     * @var ProductMetadata
     */
    private $productMetadata;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    public function setUp()
    {
        $this->eeConfig = $this->getMock(ConfigInterface::class);
        $this->eeConfig->method(self::GET_VALUE)->withConsecutive(
            ['credentials/base_url'],
            ['credentials/http_user'],
            ['credentials/http_pass']
        )->willReturnOnConsecutiveCalls(self::BASE_URL, 'user', 'pass');

        $this->methodConfig = $this->getMock(ConfigInterface::class);
        $this->methodConfig->method(self::GET_VALUE)->withConsecutive(
            ['merchant_account_id'],
            ['secret']
        )->willReturnOnConsecutiveCalls('account_id_123', 'secret_key');

        $this->productMetadata = $this->getMockBuilder(ProductMetadata::class)
            ->disableOriginalConstructor()->getMock();
        $this->productMetadata->method('getName')->willReturn('Magento');
        $this->productMetadata->method('getEdition')->willReturn('Community');
        $this->productMetadata->method('getVersion')->willReturn(self::MAGENTO_VERSION);

        $this->moduleList = $this->getMock(ModuleListInterface::class);
        $this->moduleList->method('getOne')
            ->with(self::WIRECARD_EE_MODULE_NAME)
            ->willReturn(['setup_version' => self::WIRECARD_EE_VERSION]);
    }

    public function testCreateWithEmptyPaymentCode()
    {
        $configFactory = new PaymentSdkConfigFactory(
            $this->eeConfig,
            $this->methodConfig,
            $this->productMetadata,
            $this->moduleList
        );
        $configFromFactory = $configFactory->create();

        $config = new Config(self::BASE_URL, 'user', 'pass');
        $config->setShopInfo('Magento Community Edition', self::MAGENTO_VERSION);
        $config->setPluginInfo(self::WIRECARD_EE_MODULE_NAME, self::WIRECARD_EE_VERSION);

        $this->assertEquals($config, $configFromFactory);
    }

    public function testCreateWithPaymentCode()
    {
        $configFactory = new PaymentSdkConfigFactory(
            $this->eeConfig,
            $this->methodConfig,
            $this->productMetadata,
            $this->moduleList
        );
        $configFromFactory = $configFactory->create('paypal');

        $config = new Config(self::BASE_URL, 'user', 'pass');
        $config->setShopInfo('Magento Community Edition', self::MAGENTO_VERSION);
        $config->setPluginInfo(self::WIRECARD_EE_MODULE_NAME, self::WIRECARD_EE_VERSION);

        $paypalConfig = new PaymentMethodConfig('paypal', 'account_id_123', 'secret_key');
        $config->add($paypalConfig);

        $this->assertEquals($config, $configFromFactory);
    }
}
