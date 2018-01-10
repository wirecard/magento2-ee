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
