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

namespace Wirecard\ElasticEngine\Gateway\Http;

use Magento\Framework\App\ProductMetadata;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Payment\Gateway\ConfigFactoryInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;

/**
 * Class PaymentSDKConfig
 * @package Wirecard\ElasticEngine\Gateway\Http
 */
class PaymentSdkConfigFactory implements ConfigFactoryInterface
{
    const MODULE_NAME = 'Wirecard_ElasticEngine';

    /**
     * @var ConfigInterface
     */
    private $eeConfig;

    /**
     * @var ConfigInterface
     */
    private $method;

    /**
     * @var ProductMetadata
     */
    private $productMetadata;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * PaymentSDKConfigFactory constructor.
     * @param ConfigInterface $eeConfig
     * @param ConfigInterface $methodConfig
     * @param ProductMetadata $productMetadata
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        ConfigInterface $eeConfig,
        ConfigInterface $methodConfig,
        ProductMetadata $productMetadata,
        ModuleListInterface $moduleList
    )
    {
        $this->eeConfig = $eeConfig;
        $this->method = $methodConfig;
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
    }

    /**
     * @param null|string $paymentCode
     * @param null|string $pathPattern
     * @return Config
     */
    public function create($paymentCode = null, $pathPattern = null)
    {
        $config = new Config(
            $this->eeConfig->getValue('credentials/base_url'),
            $this->eeConfig->getValue('credentials/http_user'),
            $this->eeConfig->getValue('credentials/http_pass')
        );

        $config->setShopInfo($this->productMetadata->getName(), $this->productMetadata->getVersion());
        $config->setPluginInfo(self::MODULE_NAME, $this->moduleList->getOne(self::MODULE_NAME)['setup_version']);

        if ($paymentCode !== null) {
            $methodConfig = new PaymentMethodConfig(
                $paymentCode,
                $this->method->getValue('merchant_account_id'),
                $this->method->getValue('secret')
            );
            $config->add($methodConfig);
        }

        return $config;
    }
}