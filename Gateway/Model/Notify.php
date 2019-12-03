<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Model;

use InvalidArgumentException;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\Data\PaymentTokenInterfaceFactory;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\PaymentToken;
use Magento\Vault\Model\ResourceModel\PaymentToken as PaymentTokenResourceModel;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Gateway\Helper;
use Wirecard\ElasticEngine\Gateway\Helper\TransactionTypeMapper;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\ElasticEngine\Observer\CreditCardDataAssignObserver;
use Wirecard\PaymentSdk\Entity\Status;
use Wirecard\PaymentSdk\Exception\MalformedResponseException;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;

/**
 * @since 2.1.0
 */

/**
 * Class Notify
 *
 * @package Wirecard\ElasticEngine\Gateway\Model
 */
class Notify
{
    /**
     * @var TransactionServiceFactory
     */
    private $transactionServiceFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @var bool
     */
    private $canCaptureInvoice;

    /**
     * @var PaymentTokenInterfaceFactory
     */
    protected $paymentTokenFactory;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    protected $paymentExtensionFactory;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var PaymentTokenResourceModel
     * @since 2.0.1
     */
    protected $paymentTokenResourceModel;

    /**
     * @var Helper\Order
     */
    private $orderHelper;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var TransactionTypeMapper
     * @since 2.2.2
     */
    private $transactionTypeMapper;

    /**
     * Notify constructor.
     *
     * @param TransactionServiceFactory $transactionServiceFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param Helper\Order $orderHelper
     * @param InvoiceService $invoiceService
     * @param Transaction $transaction
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param PaymentTokenInterfaceFactory $paymentTokenFactory
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param PaymentTokenResourceModel $paymentTokenResourceModel
     * @param EncryptorInterface $encryptor
     * @param TransactionTypeMapper $transactionTypeMapper
     *
     * @since 2.0.1 Add PaymentTokenResourceModel
     * @since 2.2.2 Add TransactionTypeMapper
     */
    public function __construct(
        TransactionServiceFactory $transactionServiceFactory,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        Helper\Order $orderHelper,
        InvoiceService $invoiceService,
        Transaction $transaction,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        PaymentTokenInterfaceFactory $paymentTokenFactory,
        PaymentTokenManagementInterface $paymentTokenManagement,
        PaymentTokenResourceModel $paymentTokenResourceModel,
        EncryptorInterface $encryptor,
        TransactionTypeMapper $transactionTypeMapper
    ) {
        $this->transactionServiceFactory = $transactionServiceFactory;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->orderHelper = $orderHelper;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->canCaptureInvoice = false;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->paymentTokenResourceModel = $paymentTokenResourceModel;
        $this->encryptor = $encryptor;
        $this->transactionTypeMapper = $transactionTypeMapper;
    }

    /**
     * @param Response $response
     *
     * @throws LocalizedException
     */
    public function process(Response $response)
    {
        $orderId = $response->getCustomFields()->get('orderId');
        if (is_null($orderId)) {
            $this->logger->error('No orderID found in custom fields');

            return;
        }

        try {
            $order = $this->orderHelper->getOrderByIncrementId($orderId);
        } catch (NoSuchEntityException $e) {
            $this->logger->warning(sprintf('Order with orderID %s not found.', $orderId));

            return;
        }

        if ($response instanceof SuccessResponse) {
            if ($response->getPaymentMethod() === 'masterpass') {
                return;
            }
            $this->handleSuccess($order, $response);
        } elseif ($response instanceof FailureResponse) {
            foreach ($response->getStatusCollection() as $status) {
                /**
                 * @var Status $status
                 */
                $this->logger->error(sprintf('Error occured: %s (%s)', $status->getDescription(), $status->getCode()));
            }

            $order->cancel();
            $this->orderRepository->save($order);
        } else {
            $this->logger->warning(sprintf('Unexpected result object for notifications.'));
        }
    }

    /**
     * @param $xmlString
     *
     * @return FailureResponse|InteractionResponse|Response|SuccessResponse
     */
    public function fromXmlResponse($xmlString)
    {
        try {
            $transactionService = $this->transactionServiceFactory->create();
            //handle response
            $response = $transactionService->handleNotification($xmlString);
        } catch (InvalidArgumentException $e) {
            $this->logger->error($xmlString . 'Invalid argument set: ' . $e->getMessage());
            throw $e;
        } catch (MalformedResponseException $e) {
            $this->logger->error('Response is malformed: ' . $e->getMessage());
            throw $e;
        }

        return $response;
    }

    /**
     * @param Order $order
     * @param SuccessResponse $response
     *
     * @throws LocalizedException
     */
    protected function handleSuccess($order, $response)
    {
        if ($order->getStatus() !== Order::STATE_COMPLETE) {
            $this->updateOrderState($order, Order::STATE_PROCESSING);
        }
        /**
         * @var Order\Payment $payment
         */
        $payment = $order->getPayment();
        $this->setCanCaptureInvoice($response->getTransactionType());
        $this->updatePaymentTransactionIds($payment, $response);
        if ($this->canCaptureInvoice) {
            $this->captureInvoice($order, $response);
        }

        try {
            if ($payment->getAdditionalInformation(CreditCardDataAssignObserver::VAULT_ENABLER)) {
                $this->saveCreditCardToken($response, $order->getCustomerId(), $payment);
            }
        } catch (AlreadyExistsException $e) {
            // just in the case that there is a stale token, next time saving the card will succeed
            $this->removeTokenByGatewayToken($order->getCustomerId(), $response->getCardTokenId());
            // suppress exception, this error should not cause an incomplete order
        }

        $this->orderRepository->save($order);
    }

    /**
     * search for an order by id and update the state/status property
     *
     * @param OrderInterface $order
     * @param $newState
     *
     * @return OrderInterface
     */
    private function updateOrderState(OrderInterface $order, $newState)
    {
        $order->setStatus($newState);
        $order->setState($newState);
        $this->orderRepository->save($order);

        return $order;
    }

    /**
     * @param Order\Payment $payment
     * @param SuccessResponse $response
     *
     * @return Order\Payment
     */
    private function updatePaymentTransactionIds(Order\Payment $payment, SuccessResponse $response)
    {
        $payment->setTransactionId($response->getTransactionId());
        $payment->setLastTransId($response->getTransactionId());
        $additionalInfo = [];

        $responseData = $response->getData();
        if ($responseData !== []) {
            foreach ($responseData as $key => $value) {
                $additionalInfo[$key] = $value;
            }
        }
        if ($additionalInfo !== []) {
            $payment->setTransactionAdditionalInfo(Order\Payment\Transaction::RAW_DETAILS, $additionalInfo);
        }
        if ($response->getParentTransactionId() !== null) {
            $payment->setParentTransactionId($response->getParentTransactionId());
        }

        $transactionType = $response->getTransactionType();
        // map ee type to magento types, unknown types are leading to an error:
        // "We found an unsupported transaction type..."
        $transactionType = $this->transactionTypeMapper->getMappedTransactionType($transactionType);

        // Payment for this transaction type must be set explicit to NOT closed
        // @TODO: Find root cause for this special case
        if ($transactionType === TransactionInterface::TYPE_AUTH) {
            $payment->setIsTransactionClosed(false);
        }
        $payment->addTransaction($transactionType);

        return $payment;
    }

    /**
     * @param Order $order
     * @param SuccessResponse $response
     *
     * @throws LocalizedException
     */
    private function captureInvoice($order, $response)
    {
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->register();
        $invoice->pay();
        //add transactionid for invoice
        $invoice->setTransactionId($response->getTransactionId());
        $order->addRelatedObject($invoice);

        $transactionSave = $this->transaction->addObject($invoice);
        $transactionSave = $transactionSave->addObject($invoice->getOrder());
        $transactionSave->save();
        $order->addStatusHistoryComment(
            __('capture_invoice_comment', $order->getGrandTotal(), $response->getTransactionId())
        )->setIsCustomerNotified(true);
    }

    /**
     * @param $transactionType
     */
    private function setCanCaptureInvoice($transactionType)
    {
        $this->canCaptureInvoice = false;

        if ($transactionType === 'debit' || $transactionType === 'purchase') {
            $this->canCaptureInvoice = true;
        }
    }

    /**
     * @param SuccessResponse $response
     * @param $customerId
     * @param Order\Payment $payment
     *
     * @throws \Exception
     * @since 2.0.1
     */
    protected function saveCreditCardToken($response, $customerId, $payment)
    {
        $this->migrateToken($response, $customerId, $payment);

        /** @var PaymentTokenInterface $paymentToken */
        $paymentToken = $this->paymentTokenFactory->create();
        $paymentToken->setGatewayToken($response->getCardTokenId());
        $paymentToken->setIsActive(true);
        $paymentToken->setExpiresAt(date('d-m-Y', strtotime(date('d-m-Y', time()) . " + 1 year")));
        $paymentToken->setIsVisible(true);
        $paymentToken->setCustomerId($customerId);
        $paymentToken->setPaymentMethodCode($payment->getMethod());
        $paymentToken->setTokenDetails(json_encode([
            'type' => '',
            'maskedCC' => substr($response->getMaskedAccountNumber(), -4),
            'expirationDate' => 'xx-xxxx'
        ]));
        $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));

        if (null !== $paymentToken) {
            /** @var \Magento\Sales\Api\Data\OrderPaymentExtensionInterface $extensionAttributes */
            $extensionAttributes = $payment->getExtensionAttributes();
            if (null === $extensionAttributes) {
                $extensionAttributes = $this->paymentExtensionFactory->create();
                $payment->setExtensionAttributes($extensionAttributes);
            }
            $this->paymentTokenManagement->saveTokenWithPaymentLink($paymentToken, $payment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }
    }

    /**
     * @param PaymentTokenInterface $paymentToken
     *
     * @return string
     * @since 2.0.1
     */
    protected function generatePublicHash(PaymentTokenInterface $paymentToken)
    {
        $customerId = '';
        if ($paymentToken->getCustomerId()) {
            $customerId = $paymentToken->getCustomerId();
        }

        $hashKey = sprintf(
            '%s%s%s%s%s',
            $paymentToken->getGatewayToken(),
            $customerId,
            $paymentToken->getPaymentMethodCode(),
            $paymentToken->getType(),
            $paymentToken->getTokenDetails()
        );

        return $this->encryptor->getHash($hashKey);
    }

    /**
     * remove tokens with outdated hash credentials
     * check if a token is found with the old hash for a customer and remove it
     *
     * @param SuccessResponse $response
     * @param $customerId
     * @param Order\Payment $payment
     *
     * @throws \Exception
     * @since 2.0.1
     */
    protected function migrateToken($response, $customerId, $payment)
    {
        $hash = $this->generatePublicLegacyHash($response, $customerId, $payment);

        /** @var PaymentToken $token */
        $token = $this->paymentTokenManagement->getByPublicHash($hash, $customerId);
        if (!empty($token)) {
            // do not use the PaymentTokenRepository, it just deactivates the token on delete
            $this->paymentTokenResourceModel->delete($token);

            $this->removeTokenByGatewayToken($customerId, $response->getCardTokenId());
        }
    }

    /**
     * generate the legacy hash, do not use this function anywhere else
     *
     * @param SuccessResponse $response
     * @param $customerId
     * @param Order\Payment $payment
     *
     * @return string
     * @since 2.0.1
     */
    private function generatePublicLegacyHash($response, $customerId, $payment)
    {
        $paymentToken = $this->paymentTokenFactory->create();

        $hashKey = $response->getCardTokenId();
        if ($customerId) {
            $hashKey = $customerId;
        }
        $hashKey .= $payment->getMethod() . $paymentToken->getType();

        return $this->encryptor->getHash($hashKey);
    }

    /**
     * remove a token from the database by gateway token (cardTokenId)
     * there might be some stale tokens with the same gateway_token
     * payment_method_code, customer_id and gateway_token must be unique
     *
     * @param $customerId
     * @param $gatewayToken
     *
     * @throws LocalizedException
     * @since 2.0.1
     */
    protected function removeTokenByGatewayToken($customerId, $gatewayToken)
    {
        $this->paymentTokenResourceModel->getConnection()->delete($this->paymentTokenResourceModel->getMainTable(), [
            'customer_id = ?' => $customerId,
            'gateway_token = ?' => $gatewayToken
        ]);
    }
}
