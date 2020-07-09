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
use stdClass;
use Wirecard\ElasticEngine\Gateway\Helper\NestedObject;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Transaction\AlipayCrossborderTransaction;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * Class used for updating the transaction
 *
 * @since 2.1.0
 */
class TransactionUpdater
{
    const FIELD_TRANSACTION_ID = 'transaction-id';
    const FIELD_MAID = 'merchant-account-id';
    const FIELD_PAYMENTMETHOD1 = 'payment-methods.0.name';
    const FIELD_PAYMENTMETHOD2 = 'payment-method';

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
     * @var NestedObject
     */
    protected $nestedObjectHelper;

    /**
     * PaymentStatus constructor.
     *
     * @param LoggerInterface $logger
     * @param TransactionServiceFactory $transactionServiceFactory
     * @param Collection $transactionCollection
     * @param Payment\Transaction\Repository $transactionRepository
     * @param RetrieveTransaction $retreiveTransaction
     * @param Notify $notify
     * @param NestedObject $nestedObjectHelper
     */
    public function __construct(
        LoggerInterface $logger,
        TransactionServiceFactory $transactionServiceFactory,
        Collection $transactionCollection,
        Payment\Transaction\Repository $transactionRepository,
        RetrieveTransaction $retreiveTransaction,
        Notify $notify,
        NestedObject $nestedObjectHelper
    ) {
        $this->logger                    = $logger;
        $this->transactionServiceFactory = $transactionServiceFactory;
        $this->transactionCollection     = $transactionCollection;
        $this->transactionRepository     = $transactionRepository;
        $this->retrieveTransaction       = $retreiveTransaction;
        $this->notify                    = $notify;
        $this->nestedObjectHelper        = $nestedObjectHelper;
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
        // XXX ToDo: remove this filter, be aware, that authorizations can not be flagged as closed
        $this->transactionCollection->addFieldToFilter(
            'main_table.additional_information',
            ['like' => '%"has-notify":true%']
        );

        // keep this, to be able to check, whether cronjon is running or not
        $this->logger->info(sprintf(
            'WirecardTransactionUpdater::found %d payments',
            $this->transactionCollection->count()
        ));

        while (($transaction = $this->transactionCollection->fetchItem()) !== false) {
            /** @var Payment\Transaction $transaction */

            try {
                $result = $this->fetchNotify($transaction);
                if (null === $result) {
                    continue;
                }

                $this->notify->process($result);

                // transaction might have been changed by notify, refresh data
                $refreshed = $this->transactionRepository->get($transaction->getId());

                // do not close authorizations, otherwise (online) invoicing is no possible anymore
                // this only happens, if the order transaction will be updated, because the transaction-id does not
                // change between order and notify, currently this applies to cc non-3ds transactions only
                if ($refreshed->getTxnType() !== TransactionInterface::TYPE_AUTH) {
                    $refreshed->setIsClosed(true);
                    $this->transactionRepository->save($refreshed);
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
        // keep this debug log
        $logStr = sprintf(
            'WirecardTransactionUpdater::transaction:%s order:%s ',
            $transaction->getTransactionId(),
            $transaction->getOrderId()
        );

        $rawData    = null;
        $additional = json_decode($transaction->getData('additional_information'));

        $rawData = $this->nestedObjectHelper->get($additional, Order\Payment\Transaction::RAW_DETAILS);

        $params = $this->sanitizeRawData($rawData);
        if (null === $params) {
            return null;
        }

        $result = $this->retrieveTransaction->byRequestId(
            $this->getConfig($params->paymentMethod),
            $params->requestId,
            $params->maid
        );

        if (null === $result) {
            // try to get transaction by transaction-id for CC 3ds payments
            if ($params->paymentMethod === CreditCardTransaction::NAME) {
                $result = $this->retrieveTransaction->byTransactionId(
                    $this->getConfig($params->paymentMethod),
                    $params->transactionId,
                    $params->transactionType,
                    $params->maid
                );
            }
        }

        if (null === $result) {
            $this->logger->debug($logStr . 'no notify found');

            return null;
        }

        $response = $this->notify->fromXmlResponse($result);

        $this->logger->debug($logStr . 'Notification response is instance of: ' . get_class($response));
        $this->logger->debug($logStr . 'Notification: ' . json_encode($response->getData()));

        return $response;
    }

    /**
     * get a unified version of the additional information data
     *
     * @param object $rawData
     *
     * @return null|stdClass
     */
    protected function sanitizeRawData($rawData)
    {
        if (null === $rawData) {
            return null;
        }

        $ret = new stdClass();

        $ret->paymentMethod = $this->nestedObjectHelper->get($rawData, self::FIELD_PAYMENTMETHOD1);
        if (null === $ret->paymentMethod) {
            $ret->paymentMethod = $this->nestedObjectHelper->get($rawData, self::FIELD_PAYMENTMETHOD2);
        }

        $ret->requestId       = $this->nestedObjectHelper->get($rawData, RetrieveTransaction::FIELD_REQUEST_ID);
        $ret->transactionType = $this->nestedObjectHelper->get($rawData, Transaction::PARAM_TRANSACTION_TYPE);
        $ret->transactionId   = $this->nestedObjectHelper->get($rawData, self::FIELD_TRANSACTION_ID);
        $ret->maid            = $this->nestedObjectHelper->get($rawData, self::FIELD_MAID);

        // should never happen (in theory)
        if (null === $ret->requestId
            || null === $ret->paymentMethod
            || null === $ret->transactionType
            || null === $ret->transactionId
            || null === $ret->maid) {
            return null;
        }

        $ret->requestId = $this->normalizeRequestId($ret->paymentMethod, $ret->requestId);

        return $ret;
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
            $requestId = preg_replace('/-get-url$/', '', $requestId);
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
