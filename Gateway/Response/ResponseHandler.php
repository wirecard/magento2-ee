<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Response;

use Magento\Checkout\Model\Session;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Psr\Log\LoggerInterface;
use Wirecard\PaymentSdk\Entity\Status;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;

/**
 * Class ResponseHandler
 * @package Wirecard\ElasticEngine\Gateway\Response
 */
class ResponseHandler implements HandlerInterface
{
    const TRANSACTION_ID = 'transactionId';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * ResponseHandler constructor.
     * @param LoggerInterface $logger
     * @param Session $session
     * @param UrlInterface $urlBuilder
     */
    public function __construct(LoggerInterface $logger, Session $session, UrlInterface $urlBuilder)
    {
        $this->logger = $logger;
        $this->session = $session;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        /** @var $sdkResponse Response */
        $sdkResponse = $response['paymentSDK-php'];

        /** @var $paymentDO PaymentDataObjectInterface */
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment = $paymentDO->getPayment();

        // clear session variables
        $this->session->unsRedirectUrl();
        $this->session->unsFormMethod();
        $this->session->unsFormUrl();
        $this->session->unsFormFields();

        if ($sdkResponse instanceof InteractionResponse) {
            $this->session->setRedirectUrl($sdkResponse->getRedirectUrl());

            $this->setTransaction($payment, $sdkResponse);
        } elseif ($sdkResponse instanceof FormInteractionResponse) {
            $this->session->setFormMethod($sdkResponse->getMethod());
            $this->session->setFormUrl($sdkResponse->getUrl());

            $formFields = [];
            foreach ($sdkResponse->getFormFields() as $key => $value) {
                $formFields[] = [
                    'key' => $key,
                    'value' => $value
                ];
            }
            $this->session->setFormFields($formFields);

            $this->setTransaction($payment, $sdkResponse);
        } elseif ($sdkResponse instanceof SuccessResponse) {
            $wdBaseUrl = $this->urlBuilder->getRouteUrl('wirecard_elasticengine');
            $this->session->setRedirectUrl($wdBaseUrl . 'frontend/redirect');

            $this->setTransaction($payment, $sdkResponse);
        } elseif ($sdkResponse instanceof FailureResponse) {
            foreach ($sdkResponse->getStatusCollection() as $status) {
                /** @var $status Status */
                $this->logger->error(sprintf('Error occurred: %s (%s).', $status->getDescription(), $status->getCode()));
            }
        } else {
            $this->logger->warning(sprintf('Unexpected result object for notifications.'));
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @param Response $sdkResponse
     */
    private function setTransaction($payment, $sdkResponse)
    {
        $payment->setTransactionId($sdkResponse->getTransactionId());
        $payment->setLastTransId($sdkResponse->getTransactionId());
        $payment->setIsTransactionClosed(false);
        $payment->setAdditionalInformation(self::TRANSACTION_ID, $sdkResponse->getTransactionId());
        $data = $sdkResponse->getData();
        $payment->setAdditionalInformation($data);
        $additionalInfo = [];

        if ($data !== []) {
            foreach ($data as $key => $value) {
                $additionalInfo[$key] = $value;
            }
        }
        if ($additionalInfo !== []) {
            $payment->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $additionalInfo);
        }
        if (isset($additionalInfo['transaction-type']) && $additionalInfo['transaction-type'] == 'authorization') {
            $payment->addTransaction(TransactionInterface::TYPE_AUTH);
        } else {
            $payment->addTransaction(TransactionInterface::TYPE_ORDER);
        }
    }
}
