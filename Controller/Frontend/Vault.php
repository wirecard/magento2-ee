<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
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
