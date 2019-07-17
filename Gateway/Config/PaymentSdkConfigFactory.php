<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Config;

use Magento\Checkout\Exception;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Payment\Gateway\ConfigFactoryInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\CreditCardConfig;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Config\SepaConfig;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction;

/**
 * Class PaymentSDKConfig
 * @package Wirecard\ElasticEngine\Gateway\Config
 */
class PaymentSdkConfigFactory implements ConfigFactoryInterface
{
    /**
     * @const string WIRECARD_EXTENSION_HEADER_PLUGIN_NAME
     *
     * @since 1.5.2
     */
    const WIRECARD_EXTENSION_HEADER_PLUGIN_NAME = 'magento2-ee+Wirecard';

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
     * @throws Exception
     */
    public function create($paymentCode = null, $pathPattern = null)
    {
        if ($paymentCode === null) {
            $config = new Config(
                $this->eeConfig->getValue('base_url'),
                $this->eeConfig->getValue('http_user'),
                $this->eeConfig->getValue('http_pass')
            );
            return $config;
        }

        $methodConfig = $this->methodConfigs[$paymentCode];

        $config = new Config(
            $methodConfig->getValue('base_url'),
            $methodConfig->getValue('http_user'),
            $methodConfig->getValue('http_pass')
        );
        if ($paymentCode === CreditCardTransaction::NAME) {
            $paymentMethod = $this->getCreditCardConfig($methodConfig);
        } elseif (($paymentCode === SepaDirectDebitTransaction::NAME)
            || ($paymentCode === SepaCreditTransferTransaction::NAME)) {
            $paymentMethod = $this->getSepaConfig($methodConfig, $paymentCode);
        } else {
            $paymentMethod = $this->getPaymentMethodConfig($methodConfig, $paymentCode);
        }

        $config->add($paymentMethod);
        $config->setShopInfo(
            $this->productMetadata->getName() . '+' . $this->productMetadata->getEdition() . ' Edition',
            $this->productMetadata->getVersion()
        );
        $config->setPluginInfo(
            self::WIRECARD_EXTENSION_HEADER_PLUGIN_NAME,
            $this->moduleList->getOne('Wirecard_ElasticEngine')['setup_version']
        );

        return $config;
    }

    /**
     * @param $config
     * @param $name
     * @return PaymentMethodConfig
     */
    private function getPaymentMethodConfig($config, $name)
    {
        $methodConfig = new PaymentMethodConfig(
            $name,
            $config->getValue('merchant_account_id'),
            $config->getValue('secret')
        );

        return $methodConfig;
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
                (float)$config->getValue('ssl_max_limit'),
                $config->getValue('default_currency')
            ));
        }

        if ($config->getValue('three_d_min_limit') !== '') {
            $methodSdkConfig->addThreeDMinLimit(new Amount(
                (float)$config->getValue('three_d_min_limit'),
                $config->getValue('default_currency')
            ));
        }

        return $methodSdkConfig;
    }

    /**
     * @param \Magento\Payment\Gateway\Config\Config $config
     * @param string $name
     * @return SepaConfig
     */
    private function getSepaConfig($config, $name)
    {
        $methodSdkConfig = new SepaConfig(
            $name,
            $config->getValue('merchant_account_id'),
            $config->getValue('secret')
        );

        if ($config->getValue('creditor_id') !== '') {
            $methodSdkConfig->setCreditorId($config->getValue('creditor_id'));
        }

        return $methodSdkConfig;
    }
}
