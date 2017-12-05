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
use Magento\Framework\View\Element\Template;
use Magento\Payment\Gateway\ConfigInterface;

class SepaMandateBlock extends Template
{
    /**
     * @var ConfigInterface
     */
    private $sepaConfig;

    /**
     * @var Template\Context $context
     */
    private $context;

    /**
     * @var Session $session
     */
    private $session;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param ConfigInterface $methodConfig
     * @param Session $session
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        ConfigInterface $methodConfig,
        Session $session,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->sepaConfig = $methodConfig;
        $this->context = $context;
        $this->session = $session;
    }

    /**
     * @return string
     */
    public function getCreditorName()
    {
        return $this->sepaConfig->getValue('creditor_name');
    }

    /**
     * @return string
     */
    public function getMandateId()
    {
        return $this->getCreditorId() . "-" . $this->session->getQuoteId() . "-" . strtotime(date("Y-m-d H:i:s"));
    }

    /**
     * @return string
     */
    public function getCreditorId()
    {
        return $this->sepaConfig->getValue('creditor_id');
    }

    /**
     * @return string
     */
    public function getStoreCity()
    {
        return $this->sepaConfig->getValue('creditor_city');
    }

    /**
     * @return boolean
     */
    public function getBankBicEnabled()
    {
        return $this->sepaConfig->getValue('enable_bic');
    }
}
