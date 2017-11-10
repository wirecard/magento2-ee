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
use Wirecard\PaymentSdk\Entity\IdealBic;

class ConfigProvider implements ConfigProviderInterface
{
    const PAYPAL_CODE = 'wirecard_elasticengine_paypal';
    const CREDITCARD_CODE = 'wirecard_elasticengine_creditcard';
    const MAESTRO_CODE = 'wirecard_elasticengine_maestro';
    const IDEAL_CODE = 'wirecard_elasticengine_ideal';

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
            'payment' => $this->getConfigForPaymentMethod(self::PAYPAL_CODE) +
                $this->getConfigForCreditCard(self::CREDITCARD_CODE) +
                $this->getConfigForCreditCard(self::MAESTRO_CODE) +
                $this->getConfigForPaymentMethod(self::IDEAL_CODE)
        ];
    }

    private function getConfigForPaymentMethod($paymentMethodName)
    {
        return [
            $paymentMethodName => [
                'logo_url' => $this->getLogoUrl($paymentMethodName),
                'ideal_bic' => $this->getIdealBic(),
            ]
        ];
    }

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
     * @return array
     */
    private function getIdealBic()
    {
        $options = [
            ['key' => IdealBic::ABNANL2A, 'label' => 'ABN Amro Bank'],
            ['key' => IdealBic::ASNBNL21, 'label' => 'ASN Bank'],
            ['key' => IdealBic::BUNQNL2A, 'label' => 'bunq'],
            ['key' => IdealBic::INGBNL2A, 'label' => 'ING'],
            ['key' => IdealBic::KNABNL2H, 'label' => 'Knab'],
            ['key' => IdealBic::RABONL2U, 'label' => 'Rabobank'],
            ['key' => IdealBic::RGGINL21, 'label' => 'Regio Bank'],
            ['key' => IdealBic::SNSBNL2A, 'label' => 'SNS Bank'],
            ['key' => IdealBic::TRIONL2U, 'label' => 'Triodos Bank'],
            ['key' => IdealBic::FVLBNL22, 'label' => 'Van Lanschot Bankiers']
        ];
        return $options;
    }
}
