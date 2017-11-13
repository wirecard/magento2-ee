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

namespace Wirecard\ElasticEngine\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;

class ConfigProvider implements ConfigProviderInterface
{
    const PAYPAL_CODE = 'wirecard_elasticengine_paypal';
    const CREDITCARD_CODE = 'wirecard_elasticengine_creditcard';
    const MAESTRO_CODE = 'wirecard_elasticengine_maestro';
    const SEPA_CODE = 'wirecard_elasticengine_sepa';

    /**
     * @var Repository
     */
    private $assetRepository;

    /**
     * @var TransactionServiceFactory
     */
    private $transactionServiceFactory;

    /**
     * @var Data
     */
    private $paymentHelper;
    /**
     * ConfigProvider constructor.
     * @param TransactionServiceFactory $transactionServiceFactory
     * @param Repository $assetRepo
     */
    public function __construct(TransactionServiceFactory $transactionServiceFactory, Repository $assetRepo, Data $paymentHelper)
    {
        $this->transactionServiceFactory = $transactionServiceFactory;
        $this->assetRepository = $assetRepo;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => $this->getConfigForPaymentMethod(self::PAYPAL_CODE) +
                $this->getConfigForCreditCard(self::CREDITCARD_CODE) +
                $this->getConfigForCreditCard(self::MAESTRO_CODE) +
                $this->getConfigForSepa(self::SEPA_CODE)
        ];
    }

    /**
     * @param $paymentMethodName
     * @return array
     */
    private function getConfigForPaymentMethod($paymentMethodName)
    {
        return [
            $paymentMethodName => [
                'logo_url' => $this->getLogoUrl($paymentMethodName)
            ]
        ];
    }

    /**
     * @param $paymentMethodName
     * @return array
     */
    private function getConfigForSepa($paymentMethodName)
    {
        return [
            $paymentMethodName => [
                'logo_url' => $this->getLogoUrl($paymentMethodName),
                'enable_bic' => $this->getBicEnabled()
            ]
        ];
    }

    /**
     * @param $paymentMethodName
     * @return array
     */
    private function getConfigForCreditCard($paymentMethodName)
    {
        $transactionService = $this->transactionServiceFactory->create();
        return [
            $paymentMethodName => [
                'logo_url' => $this->getLogoUrl($paymentMethodName),
                'seamless_request_data' => json_decode($transactionService->getDataForCreditCardUi(), true)
            ]
        ];
    }

    /**
     * @param $code
     * @return string
     */
    private function getLogoUrl($code)
    {
        $logoName = substr($code, strlen('wirecard_elasticengine_')) . '.png';
        return $this->assetRepository->getUrlWithParams('Wirecard_ElasticEngine::images/' . $logoName, ['_secure' => true]);
    }

    /**
     * @return string
     */
    private function getBicEnabled()
    {
        $method = $this->paymentHelper->getMethodInstance(self::SEPA_CODE);
        return $method->getConfigData('enable_bic');
    }
}
