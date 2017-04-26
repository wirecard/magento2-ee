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

namespace Wirecard\ElasticEngine\Controller\Frontend;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect as RedirectResult;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order;

class Success extends Action
{
    /**
     * @var Session
     */
    private $checkoutSession;

    public function __construct(Context $context, Session $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    /**
     * @return RedirectResult
     */
    public function execute()
    {
        /**
         * @var $resultRedirect RedirectResult
         */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $order = $this->checkoutSession->getLastRealOrder();
        switch ($order->getStatus()) {
            case Order::STATE_PENDING_PAYMENT:
            case 'pending':
                $this->messageManager->addNoticeMessage(__('Final state of transaction could not be determined.'));
            case Order::STATE_PROCESSING:
                $resultRedirect->setPath('checkout/onepage/success', ['_secure' => true]);
                break;
            default:
                $this->checkoutSession->restoreQuote();
                $this->messageManager->addNoticeMessage(__('The payment process was not finished successful.'));
                $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
                break;
        }
        return $resultRedirect;
    }
}
