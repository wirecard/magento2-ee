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

namespace Wirecard\ElasticEngine\Block\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Gateway\ConfigInterface;

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
        array $data = [],
        ConfigInterface $methodConfig
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
        if (
            $this->getPoiPiaAction() === 'advance'
            && is_array($this->additionalInformation)
            && isset($this->additionalInformation['provider-transaction-reference-id'])
            && isset($this->additionalInformation['merchant-bank-account.0.iban'])
        ) {
            return true;
        }
        return false;
    }
}
