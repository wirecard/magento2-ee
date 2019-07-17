<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Block\Checkout;

use Magento\Framework\View\Element\Template;
use Magento\Payment\Gateway\ConfigInterface;

class PaymentPageLoader extends Template
{
    /**
     * @var ConfigInterface
     */
    private $eeConfig;
    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param ConfigInterface $eeConfig,
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        ConfigInterface $eeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->eeConfig = $eeConfig;
    }

    public function getPaymentPageLoaderUrl()
    {
        return $this->eeConfig->getValue('credentials/base_url') . '/engine/hpp/paymentPageLoader.js';
    }
}
