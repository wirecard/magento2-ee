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

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
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
    const PROVIDER_TRANSACTION_ID = 'providerTransactionId';

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
        Transaction $transaction
    ) {
        $this->transactionServiceFactory = $transactionServiceFactory;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;

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
            if ($order->getStatus() !== Order::STATE_COMPLETE) {
                /*if ($response->isValidSignature()) {
                    $this->updateOrderState($order, Order::STATE_PROCESSING);
                } else {
                    $this->updateOrderState($order, Order::STATUS_FRAUD);
                    $this->logger->warning(sprintf('Possible fraud detected in notification for order id: %s',
                        $orderId));
                }*/
                $this->updateOrderState($order, Order::STATE_PROCESSING);
            }

            /**
             * @var $payment Order\Payment
             */
            $payment = $order->getPayment();
            $this->updatePaymentTransactionIds($payment, $response);
            if ($response->getTransactionType() !== 'authorization') {
                $this->captureInvoice($order, $response);
            }
            $this->orderRepository->save($order);
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
        if ($response->getProviderTransactionId() !== null) {
            $additionalInfo[self::PROVIDER_TRANSACTION_ID] = $response->getProviderTransactionId();
        }
        if ($response->getRequestId() !== null) {
            $additionalInfo['requestId'] = $response->getRequestId();
        }
        if ($response->getProviderTransactionReference() !== null) {
            $additionalInfo['providerTransactionReferenceId'] = $response->getProviderTransactionReference();
        }

        if ($response->getMaskedAccountNumber() !== null) {
            $additionalInfo['maskedAccountNumber'] = $response->getMaskedAccountNumber();
        }

        if ($response->getCardTokenId() !== null) {
            $additionalInfo['creditCardToken'] = $response->getCardTokenId();
        }

        try {
            $additionalInfo['authorizationCode'] = $response->findElement('authorization-code');
        } catch (MalformedResponseException $e) {
            //Is only triggered if it is not included. e.g. not a credit card transaction
        }

        if ($response->getCardholderAuthenticationStatus() !== null) {
            $additionalInfo['cardholderAuthenticationStatus'] = $response->getCardholderAuthenticationStatus();
        }

        if ($additionalInfo !== []) {
            $payment->setTransactionAdditionalInfo(Order\Payment\Transaction::RAW_DETAILS, $additionalInfo);
        }
        if ($response->getParentTransactionId() !== null) {
            $payment->setParentTransactionId($response->getParentTransactionId());
        }

        $transactionType = $response->getTransactionType();
        if ('debit' === $transactionType || 'purchase' === $transactionType) {
            $transactionType = 'capture';
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
}
