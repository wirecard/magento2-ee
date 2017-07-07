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

namespace Wirecard\ElasticEngine\Gateway\Config;

use Magento\Framework\App\ProductMetadata;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Payment\Gateway\ConfigFactoryInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\CreditCardConfig;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;

/**
 * Class PaymentSDKConfig
 * @package Wirecard\ElasticEngine\Gateway\Config
 */
class PaymentSdkConfigFactory implements ConfigFactoryInterface
{
    /**
     * @var ConfigInterface
     */
    private $eeConfig;

    /**
     * @var ConfigInterface[]
     */
    private $methodConfigs;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var ProductMetadata
     */
    private $productMetadata;

    /**
     * PaymentSDKConfigFactory constructor.
     * @param ConfigInterface $eeConfig
     * @param ConfigInterface[] $methodConfigs
     * @param ProductMetadata $productMetadata
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        ConfigInterface $eeConfig,
        array $methodConfigs,
        ProductMetadata $productMetadata,
        ModuleListInterface $moduleList
    ) {
        $this->eeConfig = $eeConfig;
        $this->methodConfigs = $methodConfigs;
        $this->moduleList = $moduleList;
        $this->productMetadata = $productMetadata;
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

        $publicKey = $this->eeConfig->getValue('settings/public_key');
        if ('' !== trim($publicKey)) {
            $config->setPublicKey($publicKey);
        }

        foreach ($this->methodConfigs as $name => $methodConfig) {
            if ($name === CreditCardTransaction::NAME) {
                $methodSdkConfig = $this->getCreditCardConfig($methodConfig);
            } else {
                $methodSdkConfig = new PaymentMethodConfig(
                    $name,
                    $methodConfig->getValue('merchant_account_id'),
                    $methodConfig->getValue('secret')
                );
            }

            $config->add($methodSdkConfig);
        }

        $config->setShopInfo(
            $this->productMetadata->getName() . ' ' . $this->productMetadata->getEdition() . ' Edition',
            $this->productMetadata->getVersion()
        );
        $config->setPluginInfo(
            'Wirecard_ElasticEngine',
            $this->moduleList->getOne('Wirecard_ElasticEngine')['setup_version']
        );

        return $config;
    }

    /**
     * @param \Magento\Payment\Gateway\Config\Config $config
     * @return CreditCardConfig
     */
    private function getCreditCardConfig($config)
    {
        $methodSdkConfig = new CreditCardConfig(
            $config->getValue('merchant_account_id'),
            $config->getValue('secret')
        );

        if ($config->getValue('three_d_merchant_account_id') !== '') {
            $methodSdkConfig->setThreeDCredentials(
                $config->getValue('three_d_merchant_account_id'),
                $config->getValue('three_d_secret')
            );
        }

        if ($config->getValue('ssl_max_limit') !== '') {
            $methodSdkConfig->addSslMaxLimit(new Amount(
                $config->getValue('ssl_max_limit'),
                $this->eeConfig->getValue('settings/default_currency')
            ));
        }

        if ($config->getValue('three_d_min_limit') !== '') {
            $methodSdkConfig->addThreeDMinLimit(new Amount(
                $config->getValue('three_d_min_limit'),
                $this->eeConfig->getValue('settings/default_currency')
            ));
        }

        return $methodSdkConfig;
    }
}
