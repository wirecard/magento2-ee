<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Controller\Frontend;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\Layout;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Gateway\Helper;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\TransactionService;

/**
 * Class used for executing callback
 *
 * Class Callback
 * @method Http getRequest()
 * @since 3.1.2 Reworked handling of callback
 */
class Callback extends Action
{
    const REDIRECT_URL = 'redirect-url';

    /**
     * @var Session
     */
    private $session;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TransactionServiceFactory
     */
    private $transactionServiceFactory;

    /**
     * @var Helper\Payment
     */
    private $paymentHelper;

    /**
     * Callback constructor.
     *
     * @param Context $context
     * @param Session $session
     * @param LoggerInterface $logger
     * @param TransactionServiceFactory $transactionServiceFactory
     * @param Helper\Payment $paymentHelper
     */
    public function __construct(
        Context $context,
        Session $session,
        LoggerInterface $logger,
        TransactionServiceFactory $transactionServiceFactory,
        Helper\Payment $paymentHelper
    ) {
        parent::__construct($context);
        $this->session = $session;
        $this->baseUrl = $context->getUrl()->getRouteUrl('wirecard_elasticengine');
        $this->logger = $logger;
        $this->transactionServiceFactory = $transactionServiceFactory;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws LocalizedException
     * @throws \Http\Client\Exception
     * @since 3.1.2 Update return results, adding of page result for three d flow
     */
    public function execute()
    {
        /** Non redirect payment methods */
        $result = $this->createRedirectResult([self::REDIRECT_URL => $this->baseUrl . 'frontend/redirect']);

        /** Credit Card three d payments */
        if ($this->isCreditCardThreeD()) {
            $result = $this->handleThreeDTransactions(
                $this->getRequest()->getPost()->toArray()
            );
            return $result;
        }

        /** Redirect payment methods */
        if ($this->session->hasRedirectUrl()) {
            $result = $this->createRedirectResult([self::REDIRECT_URL => $this->session->getRedirectUrl()]);
            $this->session->unsRedirectUrl();
        }

        if ($this->session->hasFormUrl()) {
            $data['form-url'] = $this->session->getFormUrl();
            $data['form-method'] = $this->session->getFormMethod();
            $data['form-fields'] = $this->session->getFormFields();

            $this->session->unsFormUrl();
            $this->session->unsFormMethod();
            $this->session->unsFormFields();
            $result = $this->createRedirectResult($data);
        }

        return $result;
    }

    /**
     * Handle callback with acs url - jsresponse
     *
     * @param $response
     *
     * @return mixed
     * @throws LocalizedException
     * @throws \Http\Client\Exception
     * @since 3.1.2 Update handling
     */
    private function handleThreeDTransactions($response)
    {
        try {
            /** @var TransactionService $transactionService */
            $transactionService = $this->transactionServiceFactory->create(CreditCardTransaction::NAME);
            /** @var Response $response */
            $response = $transactionService->handleResponse($response['jsresponse']);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        /** @var SuccessResponse|InteractionResponse|FormInteractionResponse $response */
        $order = $this->session->getLastRealOrder();
        $this->paymentHelper->addTransaction($order->getPayment(), $response, true);

        /** @var Layout $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        $block = $page->getLayout()->getBlock('frontend.creditcardthreedform');
        $block->setResponse($response);
        $page->setHttpResponseCode('200');

        return $page;
    }

    /**
     * @return bool
     * @since 3.1.2
     */
    private function isCreditCardThreeD()
    {
        if ($this->getRequest()->getParam('jsresponse')) {
            return true;
        }

        return false;
    }

    /**
     * @param array $formData
     * @return Json
     * @since 3.1.2
     */
    private function createRedirectResult($formData)
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setHttpResponseCode('200');
        $result->setData([
            'status' => 'OK',
            'data' => $formData
        ]);

        return $result;
    }
}
