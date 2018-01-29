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

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\Data\PaymentTokenInterfaceFactory;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\PaymentSdk\Entity\Status;
use Wirecard\PaymentSdk\Exception\MalformedResponseException;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;

/**
 * Class Notify
 * @package Wirecard\ElasticEngine\Controller\Frontend
 * @method \Magento\Framework\App\Request\Http getRequest()
 */
class Notify extends Action
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
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

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
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * Notify constructor.
     * @param Context $context
     * @param TransactionServiceFactory $transactionServiceFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param InvoiceService $invoiceService
     * @param Transaction $transaction
     */
    public function __construct(
        Context $context,
        TransactionServiceFactory $transactionServiceFactory,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        InvoiceService $invoiceService,
        Transaction $transaction,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        PaymentTokenInterfaceFactory $paymentTokenFactory,
        PaymentTokenManagementInterface $paymentTokenManagement,
        EncryptorInterface $encryptor
    ) {
        $this->transactionServiceFactory = $transactionServiceFactory;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->canCaptureInvoice = false;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->encryptor = $encryptor;

        parent::__construct($context);
    }

    /**
     * Dispatch request
     *
     * @throws \InvalidArgumentException
     * @throws MalformedResponseException
     */
    public function execute()
    {
        //get the raw request body
        $payload = $this->getRequest()->getContent();
        $this->logger->debug('Engine response: ' . $payload);
        try {
            $transactionService = $this->transactionServiceFactory->create();
            //handle response
            $response = $transactionService->handleNotification($payload);
        } catch (\InvalidArgumentException $e) {
            $this->logger->error('Invalid argument set: ' . $e->getMessage());
            throw $e;
        } catch (MalformedResponseException $e) {
            $this->logger->error('Response is malformed: ' . $e->getMessage());
            throw $e;
        }

        $this->logger->info('Notification response is instance of: ' . get_class($response));

        //retrieve order id from response
        $orderId = $response->getCustomFields()->get('orderId');

        try {
            $order = $this->getOrderByIncrementId($orderId);
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
                 * @var $status Status
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
     * @param $order
     * @param SuccessResponse $response
     */
    private function handleSuccess($order, $response)
    {
        if ($order->getStatus() !== Order::STATE_COMPLETE) {
            $this->updateOrderState($order, Order::STATE_PROCESSING);
        }
        /**
         * @var $payment Order\Payment
         */
        $payment = $order->getPayment();
        $this->setCanCaptureInvoice($response->getTransactionType());
        $this->updatePaymentTransactionIds($payment, $response);
        if ($this->canCaptureInvoice) {
            $this->captureInvoice($order, $response);
        }

        if ($response->getCustomFields()->get('vaultEnabler') === "true") {
            $this->saveCreditCardToken($response, $order->getCustomerId(), $payment);
        }

        $this->orderRepository->save($order);
    }
    /**
     * search for an order by id and update the state/status property
     *
     * @param OrderInterface $order
     * @param $newState
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
        if ($this->canCaptureInvoice) {
            $transactionType = 'capture';
        }
        if ($transactionType == 'check-payer-response') {
            $transactionType = 'payment';
        }
        if ($transactionType == 'authorization') {
            $payment->setIsTransactionClosed(false);
        }
        $payment->addTransaction($transactionType);

        return $payment;
    }

    /**
     * @param $orderId
     * @throws NoSuchEntityException
     * @return Order
     */
    private function getOrderByIncrementId($orderId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            OrderInterface::INCREMENT_ID,
            $orderId
        )->create();
        $result = $this->orderRepository->getList($searchCriteria);

        if (empty($result->getItems())) {
            throw new NoSuchEntityException(__('No such order.'));
        }

        $orders = $result->getItems();

        return reset($orders);
    }

    /**
     * @param SuccessResponse $response
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
            __('Captured amount of %1 online. Transaction ID: %2.', $order->getGrandTotal(), $response->getTransactionId())
        )->setIsCustomerNotified(true);
    }

    /**
     * @param $transactionType
     */
    private function setCanCaptureInvoice($transactionType)
    {
        if ($transactionType === 'debit' || $transactionType === 'purchase') {
            $this->canCaptureInvoice = true;
        } else {
            $this->canCaptureInvoice = false;
        }
    }

    private function saveCreditCardToken($response, $customerId, $payment)
    {
        /** @var PaymentTokenInterface $paymentToken */
        $paymentToken = $this->paymentTokenFactory->create();
        $paymentToken->setGatewayToken($response->getCardTokenId());
        $paymentToken->setIsActive(true);
        $paymentToken->setExpiresAt(date('d-m-Y', strtotime(date('d-m-Y', time()) . " + 1 year")));
        $paymentToken->setIsVisible(true);
        $paymentToken->setCustomerId($customerId);
        $paymentToken->setPaymentMethodCode($payment->getMethod());
        $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));

        $paymentToken->setTokenDetails(json_encode([
            'type' => '',
            'maskedCC' => substr($response->getMaskedAccountNumber(), -4),
            'expirationDate' => 'xx-xxxx'
        ]));

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

    private function generatePublicHash(PaymentTokenInterface $paymentToken)
    {
        $hashKey = $paymentToken->getGatewayToken();
        if ($paymentToken->getCustomerId()) {
            $hashKey = $paymentToken->getCustomerId();
        }

        $hashKey .= $paymentToken->getPaymentMethodCode()
            . $paymentToken->getType()
            . $paymentToken->getTokenDetails();

        return $this->encryptor->getHash($hashKey);
    }
}
