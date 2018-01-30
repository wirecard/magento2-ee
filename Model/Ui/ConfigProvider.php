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

namespace Wirecard\ElasticEngine\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\PaymentSdk\Entity\IdealBic;

class ConfigProvider implements ConfigProviderInterface
{
    const PAYPAL_CODE = 'wirecard_elasticengine_paypal';
    const CREDITCARD_CODE = 'wirecard_elasticengine_creditcard';
    const MAESTRO_CODE = 'wirecard_elasticengine_maestro';
    const SEPA_CODE = 'wirecard_elasticengine_sepa';
    const SOFORT_CODE = 'wirecard_elasticengine_sofortbanking';
    const IDEAL_CODE = 'wirecard_elasticengine_ideal';
    const RATEPAYINVOICE_CODE = 'wirecard_elasticengine_ratepayinvoice';
    const RATEPAYINSTALL_CODE = 'wirecard_elasticengine_ratepayinstall';
    const ALIPAYXBORDER_CODE = 'wirecard_elasticengine_alipayxborder';
    const POIPIA_CODE = 'wirecard_elasticengine_poipia';
    const MASTERPASS_CODE = 'wirecard_elasticengine_masterpass';
    const UPI_CODE = 'wirecard_elasticengine_unionpayinternational';
    const CREDITCARD_VAULT_CODE = 'wirecard_elasticengine_cc_vault';

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
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Resolver
     */
    private $store;

    /**
     * ConfigProvider constructor.
     * @param TransactionServiceFactory $transactionServiceFactory
     * @param Repository $assetRepo
     * @param Data $paymentHelper
     * @param Session $session
     * @param Resolver $store
     */
    public function __construct(TransactionServiceFactory $transactionServiceFactory, Repository $assetRepo, Data $paymentHelper, Session $session, Resolver $store)
    {
        $this->transactionServiceFactory = $transactionServiceFactory;
        $this->assetRepository = $assetRepo;
        $this->paymentHelper = $paymentHelper;
        $this->checkoutSession = $session;
        $this->store = $store;
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
                $this->getConfigForCreditCardWithVault(self::CREDITCARD_CODE) +
                $this->getConfigForCreditCard(self::MAESTRO_CODE) +
                $this->getConfigForSepa(self::SEPA_CODE) +
                $this->getConfigForPaymentMethod(self::SOFORT_CODE) +
                $this->getConfigForPaymentMethod(self::IDEAL_CODE) +
                $this->getConfigForRatepay(self::RATEPAYINVOICE_CODE) +
                $this->getConfigForRatepay(self::RATEPAYINSTALL_CODE) +
                $this->getConfigForPaymentMethod(self::ALIPAYXBORDER_CODE) +
                $this->getConfigForPaymentMethod(self::POIPIA_CODE) +
                $this->getConfigForPaymentMethod(self::MASTERPASS_CODE) +
                $this->getConfigForUpi(self::UPI_CODE)
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
                'logo_url' => $this->getLogoUrl($paymentMethodName),
                'ideal_bic' => $this->getIdealBic()
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
    private function getConfigForRatepay($paymentMethodName)
    {
        return [
            $paymentMethodName => [
                'logo_url' => $this->getLogoUrl($paymentMethodName),
                'ratepay_script' => $this->getRatepayScript($paymentMethodName)
            ]
        ];
    }

    /**
     * @param $paymentMethodName
     * @return array
     */
    private function getConfigForCreditCard($paymentMethodName)
    {
        $locale = $this->store->getLocale();
        $transactionService = $this->transactionServiceFactory->create('creditcard');
        return [
            $paymentMethodName => [
                'logo_url' => $this->getLogoUrl($paymentMethodName),
                'seamless_request_data' => json_decode($transactionService->getDataForCreditCardUi($locale), true)
            ]
        ];
    }

    /**
     * @param $paymentMethodName
     * @return array
     */
    private function getConfigForCreditCardWithVault($paymentMethodName)
    {
        $locale = $this->store->getLocale();
        $transactionService = $this->transactionServiceFactory->create('creditcard');
        return [
            $paymentMethodName => [
                'logo_url' => $this->getLogoUrl($paymentMethodName),
                'seamless_request_data' => json_decode($transactionService->getDataForCreditCardUi($locale), true),
                'vaultCode' => ConfigProvider::CREDITCARD_VAULT_CODE
            ]
        ];
    }

    /**
     * @param $paymentMethodName
     * @return array
     */
    private function getConfigForUpi($paymentMethodName)
    {
        $locale = $this->store->getLocale();
        $transactionService = $this->transactionServiceFactory->create('unionpayinternational');
        return [
            $paymentMethodName => [
                'logo_url' => $this->getLogoUrl($paymentMethodName),
                'seamless_request_data' => json_decode($transactionService->getDataForUpiUi($locale), true)
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

    /**
     * Set deviceIdent for ratepay script
     */
    private function setInvoiceDeviceIdent()
    {
        $transactionService = $this->transactionServiceFactory->create('ratepayinvoice');
        if (!strlen($this->checkoutSession->getData('invoiceDeviceIdent'))) {
            $deviceIdent = $transactionService->getRatePayInvoiceDeviceIdent();
            $this->checkoutSession->setData('invoiceDeviceIdent', $deviceIdent);
        }
    }

    /**
     * Set deviceIdent for ratepay script
     */
    private function setInstallmentDeviceIdent()
    {
        $transactionService = $this->transactionServiceFactory->create('ratepayinstall');
        if (!strlen($this->checkoutSession->getData('installmentDeviceIdent'))) {
            $deviceIdent = $transactionService->getRatePayInstallmentDeviceIdent();
            $this->checkoutSession->setData('installmentDeviceIdent', $deviceIdent);
        }
    }

    /**
     * @param $code
     * @return string
     */
    private function getRatepayScript($code)
    {
        $this->setInstallmentDeviceIdent();
        $deviceIdent = $this->checkoutSession->getData('installmentDeviceIdent');
        if ($code == self::RATEPAYINVOICE_CODE) {
            $this->setInvoiceDeviceIdent();
            $deviceIdent = $this->checkoutSession->getData('invoiceDeviceIdent');
        }
        $script = '
        <script>
        var di = {t:\'' . $deviceIdent . '\',v:\'WDWL\',l:\'Checkout\'};
        </script>
        <script type=\'text/javascript\' src=\'//d.ratepay.com/' . $deviceIdent . '/di.js\'></script>
        <noscript>
            <link rel=\'stylesheet\' type=\'text/css\' href=\'//d.ratepay.com/di.css?t=' . $deviceIdent . '&v=WDWL&l=Checkout\'>
        </noscript>
        <object type=\'application/x-shockwave-flash\' data=\'//d.ratepay.com/WDWL/c.swf\' width=\'0\' height=\'0\'>
            <param name=\'movie\' value=\'//d.ratepay.com/WDWL/c.swf\' />
            <param name=\'flashvars\' value=\'t=' . $deviceIdent . '&v=WDWL\'/>
            <param name=\'AllowScriptAccess\' value=\'always\'/>
        </object>';

        return $script;
    }
}
