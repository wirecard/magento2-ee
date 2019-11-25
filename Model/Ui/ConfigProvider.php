<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\PaymentSdk\Entity\IdealBic;

class ConfigProvider implements ConfigProviderInterface
{
    const PAYPAL_CODE = 'wirecard_elasticengine_paypal';
    const CREDITCARD_CODE = 'wirecard_elasticengine_creditcard';
    const SEPA_CODE = 'wirecard_elasticengine_sepadirectdebit';
    const SEPACREDIT_CODE = 'wirecard_elasticengine_sepacredit';
    const SOFORT_CODE = 'wirecard_elasticengine_sofortbanking';
    const IDEAL_CODE = 'wirecard_elasticengine_ideal';
    const GIROPAY_CODE = 'wirecard_elasticengine_giropay';
    const RATEPAYINVOICE_CODE = 'wirecard_elasticengine_ratepayinvoice';
    const ALIPAYXBORDER_CODE = 'wirecard_elasticengine_alipayxborder';
    const POIPIA_CODE = 'wirecard_elasticengine_poipia';
    const CREDITCARD_VAULT_CODE = 'wirecard_elasticengine_cc_vault';
    const PAYBYBANKAPP_CODE = 'wirecard_elasticengine_paybybankapp';
    const PAYMENTPAGE_LOADER = '/loader/paymentPage.js';

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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * ConfigProvider constructor.
     * @param TransactionServiceFactory $transactionServiceFactory
     * @param Repository $assetRepo
     * @param Data $paymentHelper
     * @param Session $session
     * @param Resolver $store
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        TransactionServiceFactory $transactionServiceFactory,
        Repository $assetRepo,
        Data $paymentHelper,
        Session $session,
        Resolver $store,
        StoreManagerInterface $storeManager
    ) {
        $this->transactionServiceFactory = $transactionServiceFactory;
        $this->assetRepository = $assetRepo;
        $this->paymentHelper = $paymentHelper;
        $this->checkoutSession = $session;
        $this->store = $store;
        $this->storeManager = $storeManager;
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
                $this->getConfigForSepa(self::SEPA_CODE) +
                $this->getConfigForPaymentMethod(self::SOFORT_CODE) +
                $this->getConfigForPaymentMethod(self::IDEAL_CODE) +
                $this->getConfigForPaymentMethod(self::GIROPAY_CODE) +
                $this->getConfigForRatepay(self::RATEPAYINVOICE_CODE) +
                $this->getConfigForPaymentMethod(self::ALIPAYXBORDER_CODE) +
                $this->getConfigForPaymentMethod(self::POIPIA_CODE) +
                $this->getConfigForPaymentMethod(self::PAYBYBANKAPP_CODE)
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
                'ratepay_script' => $this->getRatepayScript(),
                'billing_equals_shipping' => (bool)$this->isBillingEqualShippingAddress(
                    self::RATEPAYINVOICE_CODE
                )
            ]
        ];
    }

    /**
     * @param $paymentMethodName
     * @return array
     */
    private function getConfigForCreditCardWithVault($paymentMethodName)
    {
        return [
            $paymentMethodName => [
                'logo_url' => $this->getLogoUrl($paymentMethodName),
                'vaultCode' => ConfigProvider::CREDITCARD_VAULT_CODE,
                'wpp_url' => $this->getWppUrl(self::CREDITCARD_CODE),
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
        return $this->assetRepository->getUrlWithParams(
            'Wirecard_ElasticEngine::images/' . $logoName,
            ['_secure' => true]
        );
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
     * @return string
     */
    private function getRatepayScript()
    {
        $this->setInvoiceDeviceIdent();
        $deviceIdent = $this->checkoutSession->getData('invoiceDeviceIdent');
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

    /**
     * Returns the wpp script based on configured wpp url for credit card form
     *
     * @param $paymentCode
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getWppUrl($paymentCode)
    {
        $method = $this->paymentHelper->getMethodInstance($paymentCode);
        return $method->getConfigData('wpp_url') . self::PAYMENTPAGE_LOADER;
    }

    /**
     * Return if the billing and shipping address needs to be same
     *
     * @param string $paymentCode
     * @return string
     * @since 1.3.7
     */
    private function isBillingEqualShippingAddress($paymentCode)
    {
        $method = $this->paymentHelper->getMethodInstance($paymentCode);
        return $method->getConfigData('billing_shipping_address_identical');
    }
}
