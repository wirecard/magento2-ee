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

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Redirect as RedirectResult;
use Magento\Framework\Controller\ResultFactory;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\Transaction\RatepayInstallmentTransaction;

/**
 * Class Redirect
 * @package Wirecard\ElasticEngine\Controller\Frontend
 * @method Http getRequest()
 */
class Redirect extends Action
{
    const CHECKOUT_URL = 'checkout/cart';
    const PAYMENT_ERROR = 'An error occurred during the payment process. Please try again.';

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var TransactionServiceFactory
     */
    private $transactionServiceFactory;

    /**
     * Redirect constructor.
     * @param Context $context
     * @param Session $checkoutSession
     * @param TransactionServiceFactory $transactionServiceFactory
     */
    public function __construct(Context $context, Session $checkoutSession, TransactionServiceFactory $transactionServiceFactory)
    {
        $this->checkoutSession = $checkoutSession;
        $this->transactionServiceFactory = $transactionServiceFactory;
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
        if ($this->getRequest()->isPost()) {
            $method = $this->getPaymentMethod($this->getRequest()->getPost()->toArray());
            $transactionService = $this->transactionServiceFactory->create($method);
            $result = $transactionService->handleResponse($this->getRequest()->getPost()->toArray());
            if ($result instanceof SuccessResponse) {
                $this->setRedirectPath($resultRedirect, 'checkout/onepage/success');
            } else {
                $this->checkoutSession->restoreQuote();
                $this->messageManager->addNoticeMessage(__(self::PAYMENT_ERROR));
                $this->setRedirectPath($resultRedirect, self::CHECKOUT_URL);
            }
        } elseif ($this->getRequest()->isGet() && $this->getRequest()->getParam('request_id')) {
            //Ideal transaction
            $transactionService = $this->transactionServiceFactory->create('ideal');
            $result = $transactionService->handleResponse($this->getRequest()->getParams());
            if ($result instanceof SuccessResponse) {
                $this->setRedirectPath($resultRedirect, 'checkout/onepage/success');
            } else {
                $this->checkoutSession->restoreQuote();
                $this->messageManager->addNoticeMessage(__(self::PAYMENT_ERROR));
                $this->setRedirectPath($resultRedirect, self::CHECKOUT_URL);
            }
        } else {
            $this->checkoutSession->restoreQuote();
            $this->messageManager->addNoticeMessage(__(self::PAYMENT_ERROR));
            $this->setRedirectPath($resultRedirect, self::CHECKOUT_URL);
        }

        return $resultRedirect;
    }

    /**
     * @param RedirectResult $resultRedirect
     * @param String $path
     * @return RedirectResult
     */
    private function setRedirectPath(RedirectResult $resultRedirect, $path)
    {
        return $resultRedirect->setPath($path, ['_secure' => true]);
    }

    /**
     * @param $payload
     * @return null|string
     */
    private function getPaymentMethod($payload)
    {
        $paymentName = null;

        if (array_key_exists('MD', $payload) && array_key_exists('PaRes', $payload)) {
            $paymentName = CreditCardTransaction::NAME;
        } elseif (array_key_exists('eppresponse', $payload)) {
            $paymentName = PayPalTransaction::NAME;
        } elseif (array_key_exists('base64payload', $payload) &&
            array_key_exists('psp_name', $payload)
        ) {
            $paymentName = RatepayInstallmentTransaction::NAME;
        }

        return $paymentName;
    }
}
