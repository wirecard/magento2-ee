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
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;

class ConfigProvider implements ConfigProviderInterface
{
    const PAYPAL_CODE = 'wirecard_elasticengine_paypal';
    const CREDITCARD_CODE = 'wirecard_elasticengine_creditcard';

    /**
     * @var Repository
     */
    private $assetRepository;

    /**
     * @var TransactionServiceFactory
     */
    private $transactionServiceFactory;
    /**
     * ConfigProvider constructor.
     * @param TransactionServiceFactory $transactionServiceFactory
     * @param Repository $assetRepo
     */
    public function __construct(TransactionServiceFactory $transactionServiceFactory, Repository $assetRepo)
    {
        $this->transactionServiceFactory = $transactionServiceFactory;
        $this->assetRepository = $assetRepo;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => array_merge(
                    $this->getConfigForPaymentMethod(self::PAYPAL_CODE),
                    $this->getConfigForCreditCard()
                )
        ];
    }

    private function getConfigForPaymentMethod($paymentMethodName)
    {
        return [
            $paymentMethodName => [
                'logo_url' => $this->getLogoUrl($paymentMethodName),
            ]
        ];
    }

    private function getConfigForCreditCard()
    {
        $transactionService = $this->transactionServiceFactory->create(CreditCardTransaction::NAME);
        return [
            self::CREDITCARD_CODE => [
                'logo_url' => $this->getLogoUrl(self::CREDITCARD_CODE),
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
}
