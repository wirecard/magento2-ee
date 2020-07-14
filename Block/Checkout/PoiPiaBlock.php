<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Block\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Gateway\ConfigInterface;

/**
 * Class for POI/PIA checkout
 */
class PoiPiaBlock extends Template
{

    /** @var Template\Context $context */
    private $context;

    /** @var Session $session */
    private $session;

    /** @var array $additionalInformation */
    private $additionalInformation;

    /** @var Data $pricingHelper */
    private $pricingHelper;

    /** @var float $grandTotal */
    private $grandTotal;

    /** @var  ConfigInterface $methodConfig */
    private $methodConfig;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param Session $session
     * @param Data $pricingHelper
     * @param array $data
     * @param ConfigInterface $methodConfig
     */
    public function __construct(
        Template\Context $context,
        Session $session,
        Data $pricingHelper,
        ConfigInterface $methodConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->context = $context;
        $this->session = $session;
        $this->pricingHelper = $pricingHelper;
        $this->methodConfig = $methodConfig;

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->session->getLastRealOrder();

        $this->grandTotal = $order->getGrandTotal();

        $payment = $order->getPayment();

        $this->additionalInformation = $payment->getAdditionalInformation();
    }

    /**
     * @return string
     */
    public function getPoiPiaAction()
    {
        return $this->methodConfig->getValue('poipia_action');
    }

    public function getMerchantBankAccount()
    {
        return [
            'iban' => $this->additionalInformation['merchant-bank-account.0.iban'],
            'bic' => $this->additionalInformation['merchant-bank-account.0.bic']
        ];
    }

    public function getAmount()
    {
        return $this->pricingHelper->currency($this->grandTotal);
    }

    public function getPtrid()
    {
        return $this->additionalInformation['provider-transaction-reference-id'];
    }

    public function isPia()
    {
        if ($this->getPoiPiaAction() === 'advance'
            && is_array($this->additionalInformation)
            && isset($this->additionalInformation['provider-transaction-reference-id'])
            && isset($this->additionalInformation['merchant-bank-account.0.iban'])
        ) {
            return true;
        }
        return false;
    }
}
