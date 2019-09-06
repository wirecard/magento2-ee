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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Gateway\Helper;
use Wirecard\PaymentSdk\Entity\Status;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;

/**
 * Class ResponseHandler
 *
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
     * @var Helper\Payment
     */
    private $paymentHelper;

    /**
     * ResponseHandler constructor.
     *
     * @param LoggerInterface $logger
     * @param Session $session
     * @param UrlInterface $urlBuilder
     * @param Helper\Payment $paymentHelper
     */
    public function __construct(
        LoggerInterface $logger,
        Session $session,
        UrlInterface $urlBuilder,
        Helper\Payment $paymentHelper
    ) {
        $this->logger = $logger;
        $this->session = $session;
        $this->urlBuilder = $urlBuilder;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
     * @throws LocalizedException
     */
    public function handle(array $handlingSubject, array $response)
    {
        /** @var Response $sdkResponse */
        $sdkResponse = $response['paymentSDK-php'];

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        // clear session variables
        $this->session->unsRedirectUrl();
        $this->session->unsFormMethod();
        $this->session->unsFormUrl();
        $this->session->unsFormFields();

        if ($sdkResponse instanceof InteractionResponse) {
            $this->session->setRedirectUrl($sdkResponse->getRedirectUrl());

            $this->paymentHelper->addTransaction($payment, $sdkResponse);
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

            $this->paymentHelper->addTransaction($payment, $sdkResponse);
        } elseif ($sdkResponse instanceof SuccessResponse) {
            $wdBaseUrl = $this->urlBuilder->getRouteUrl('wirecard_elasticengine');
            $this->session->setRedirectUrl($wdBaseUrl . 'frontend/redirect');

            $this->paymentHelper->addTransaction($payment, $sdkResponse);
        } elseif ($sdkResponse instanceof FailureResponse) {
            foreach ($sdkResponse->getStatusCollection() as $status) {
                /** @var Status $status */
                $this->logger->error(sprintf('Error occurred: %s (%s).', $status->getDescription(), $status->getCode()));
            }
        } else {
            $this->logger->warning(sprintf('Unexpected result object for notifications.'));
        }
    }
}
