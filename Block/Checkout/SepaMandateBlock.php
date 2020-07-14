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
use Magento\Framework\View\Element\Template;
use Magento\Payment\Gateway\ConfigInterface;

/**
 * Class for SEPA mandate
 */
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
