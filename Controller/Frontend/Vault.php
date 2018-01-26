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

namespace Wirecard\ElasticEngine\Controller\Frontend;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;

/**
 * Class Callback
 * @package Wirecard\ElasticEngine\Controller\Frontend
 */
class Vault extends Action
{
    const REDIRECT_URL = 'redirect-url';

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var Session
     */
    private $customerSession;
    /**
     * Callback constructor.
     * @param Context $context
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        PaymentTokenManagementInterface $paymentTokenManagement,
        Session $customerSession
    ) {
        $this->baseUrl = $context->getUrl()->getRouteUrl('wirecard_elasticengine');
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->customerSession = $customerSession;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $hash = $this->getRequest()->getParam('hash');

        /** @var PaymentTokenInterface $paymentToken */
        $token = $this->paymentTokenManagement->getByPublicHash($hash, $this->customerSession->getCustomerId());

        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData(["token_id" => $token->getGatewayToken(), "method_code" => $token->getPaymentMethodCode()]);

        return $result;
    }
}
