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
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Gateway\Helper;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;

/**
 * Class Callback
 * @package Wirecard\ElasticEngine\Controller\Frontend
 * @method Http getRequest()
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
     * @var UrlInterface
     */
    private $urlBuilder;

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
        $this->urlBuilder = $context->getUrl();
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $resultData = $this->createResultDataFromResponse();

        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData([
            'status' => 'OK',
            'data' => $resultData,
        ]);

        return $result;
    }

    /**
     * @return array|mixed
     * @throws LocalizedException
     * @since 3.0.0
     */
    private function createResultDataFromResponse()
    {
        $data = $this->initResultData();
        if ($this->getRequest()->getParam('jsresponse')) {
            $response = null;
            $response = $this->getRequest()->getPost()->toArray();
            $data = $this->handleThreeDTransactions($response);
            return $data;
        }
        if ($this->session->hasRedirectUrl()) {
            $data[self::REDIRECT_URL] = $this->session->getRedirectUrl();
            $this->session->unsRedirectUrl();
            return $data;
        }
        if ($this->session->hasFormUrl()) {
            $data['form-url'] = $this->session->getFormUrl();
            $data['form-method'] = $this->session->getFormMethod();
            $data['form-fields'] = $this->session->getFormFields();

            $this->session->unsFormUrl();
            $this->session->unsFormMethod();
            $this->session->unsFormFields();
            return $data;
        }
        $data[self::REDIRECT_URL] = $this->baseUrl . 'frontend/redirect';
        return $data;
    }

    /**
     * @return array
     * @since 3.0.0
     */
    private function initResultData()
    {
        return [
            self::REDIRECT_URL => null,
            'form-url' => null,
            'form-method' => null,
            'form-fields' => null
        ];
    }

    /**
     * Handle callback with acs url - jsresponse
     *
     * @param $response
     *
     * @return mixed
     * @throws LocalizedException
     */
    private function handleThreeDTransactions($response)
    {
        $methodName = 'creditcard';

        try {
            /** @var TransactionService $transactionService */
            $transactionService = $this->transactionServiceFactory->create($methodName);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        // Create credit card redirection url
        $wdBaseUrl    = $this->urlBuilder->getRouteUrl('wirecard_elasticengine');
        $methodAppend = '?method=' . urlencode($methodName);
        $url = $wdBaseUrl . 'frontend/redirect' . $methodAppend;

        /** @var Response $response */
        $response = $transactionService->processJsResponse($response['jsresponse'], $url);
        $data[self::REDIRECT_URL] = $this->baseUrl . 'frontend/redirect';

        /** @var SuccessResponse|InteractionResponse|FormInteractionResponse $response */
        $order = $this->session->getLastRealOrder();
        $this->paymentHelper->addTransaction($order->getPayment(), $response, true);

        if ($response instanceof FormInteractionResponse) {
            unset($data[self::REDIRECT_URL]);
            $data['form-url'] = html_entity_decode($response->getUrl());
            $data['form-method'] = $response->getMethod();
            foreach ($response->getFormFields() as $key => $value) {
                $data[$key] = html_entity_decode($value);
            }
        }

        return $data;
    }
}
