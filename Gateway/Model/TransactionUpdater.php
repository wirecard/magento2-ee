<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Model;

use Exception;
use Magento\Framework\App;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Transaction\AlipayCrossborderTransaction;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;

/**
 * @since 2.1.0
 */
class TransactionUpdater
{
    /**
     * @var Collection
     */
    protected $transactionCollection;

    /**
     * @var Payment\Transaction\Repository
     */
    protected $transactionRepository;

    /**
     * @var TransactionServiceFactory
     */
    protected $transactionServiceFactory;

    /**
     * @var RetrieveTransaction
     */
    protected $retrieveTransaction;

    /**
     * @var Notify
     */
    protected $notify;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * PaymentStatus constructor.
     *
     * @param LoggerInterface $logger
     * @param TransactionServiceFactory $transactionServiceFactory
     * @param Collection $transactionCollection
     * @param Payment\Transaction\Repository $transactionRepository
     * @param RetrieveTransaction $retreiveTransaction
     * @param Notify $notify
     */
    public function __construct(
        LoggerInterface $logger,
        TransactionServiceFactory $transactionServiceFactory,
        Collection $transactionCollection,
        Payment\Transaction\Repository $transactionRepository,
        RetrieveTransaction $retreiveTransaction,
        Notify $notify
    ) {
        $this->logger              = $logger;
        $this->transactionServiceFactory = $transactionServiceFactory;
        $this->transactionCollection     = $transactionCollection;
        $this->transactionRepository     = $transactionRepository;
        $this->retrieveTransaction = $retreiveTransaction;
        $this->notify              = $notify;
    }

    /**
     * @return App\ResponseInterface|void
     */
    public function run()
    {
        $this->transactionCollection->addFieldToFilter('is_closed', 0);
        $this->transactionCollection->join(['p' => 'sales_order_payment'], "p.entity_id = main_table.payment_id", []);
        $this->transactionCollection->addFieldToFilter('p.method', ['like' => 'wirecard_elasticengine_%']);
        $this->transactionCollection->join(['o' => 'sales_order'], "o.entity_id = main_table.order_id", []);
        $this->transactionCollection->addFieldToFilter('o.status', [
            'nin' => [
                Order::STATE_COMPLETE,
                Order::STATE_CANCELED
            ]
        ]);
        $this->transactionCollection->addFieldToFilter('main_table.additional_information',
            ['like' => '%"has-notify":true%']);

        $this->logger->debug(sprintf('WirecardTransactionUpdater::found %d payments',
            $this->transactionCollection->count()));

        while (($transaction = $this->transactionCollection->fetchItem()) !== false) {
            /** @var Payment\Transaction $transaction */

            try {
                $result = $this->fetchNotify($transaction);
                if ($result === null) {
                    continue;
                }

                $this->notify->process($result);

                // transaction might have been changed by notify, refresh data
                $t = $this->transactionRepository->get($transaction->getId());

                // do not close authorizations, otherwise (online) invoicing is no possible anymore
                // this only happens, if the order transaction will be updated, because the transaction-id does not
                // change between order and notify, currently this applies to cc non-3ds transactions only
                if ($t->getTxnType() !==  TransactionInterface::TYPE_AUTH) {
                    $t->setIsClosed(true);
                    $this->transactionRepository->save($t);
                }
            } catch (Exception $e) {
                $this->logger->error('WirecardTransactionUpdater::exception:' . $e->getMessage());
            }
        }
    }

    /**
     * fetch the notify for the given transaction and return the response object
     *
     * @param Payment\Transaction $transaction
     *
     * @return Response|null
     */
    public function fetchNotify(Payment\Transaction $transaction)
    {
        $logStr = sprintf('WirecardTransactionUpdater::transaction:%s order:%s ',
            $transaction->getTransactionId(), $transaction->getOrderId());

        $rawData    = null;
        $additional = json_decode($transaction->getData('additional_information'));
        if (!is_object($additional)) {
            return null;
        }
        if (property_exists($additional, Order\Payment\Transaction::RAW_DETAILS)) {
            $rawData = $additional->{Order\Payment\Transaction::RAW_DETAILS};
        }

        if ($rawData === null) {
            return null;
        }

        $paymentMethod = null;
        if (property_exists($rawData, 'payment-methods.0.name')) {
            $paymentMethod = $rawData->{'payment-methods.0.name'};
        } elseif (property_exists($rawData, 'payment-method')) {
            $paymentMethod = $rawData->{'payment-method'};
        }

        // should never happen (in theory)
        if (!property_exists($rawData, 'request-id')
            || !strlen($paymentMethod)
            || !property_exists($rawData, 'transaction-type')
            || !property_exists($rawData, 'transaction-id')
            || !property_exists($rawData, 'merchant-account-id')) {
            return null;
        }

        $maid      = $rawData->{'merchant-account-id'};
        $requestId = $rawData->{'request-id'};

        $requestId = $this->normalizeRequestId($paymentMethod, $requestId);

        $result = $this->retrieveTransaction->byRequestId(
            $this->getConfig($paymentMethod),
            $requestId,
            $maid);

        if ($result === false) {
            // try to get transaction by transaction-id for CC 3ds payments
            if ($paymentMethod === CreditCardTransaction::NAME) {
                $result = $this->retrieveTransaction->byTransactionId(
                    $this->getConfig($paymentMethod),
                    $rawData->{'transaction-id'},
                    $rawData->{'transaction-type'},
                    $maid);
            }
        }

        if ($result === false) {
            $this->logger->debug($logStr . 'no notify found');

            return null;
        }

        $response = $this->notify->fromXmlResponse($result);

        $this->logger->debug($logStr . 'Notification response is instance of: ' . get_class($response));

        return $response;
    }

    /**
     * @param $paymentMethod
     * @param $requestId
     *
     * @return string
     */
    protected function normalizeRequestId($paymentMethod, $requestId)
    {
        if ($paymentMethod === AlipayCrossborderTransaction::NAME) {
            // alipay has get-url appended to the request-id
            return preg_replace('/-get-url$/', '', $requestId);
        }

        return $requestId;
    }

    /**
     * @param $methodCode
     *
     * @return Config
     */
    protected function getConfig($methodCode)
    {
        if ($methodCode === 'ratepay-invoice') {
            $methodCode = RatepayInvoiceTransaction::NAME;
        }

        $transaction = $this->transactionServiceFactory->create($methodCode);

        return $transaction->getConfig();
    }
}
