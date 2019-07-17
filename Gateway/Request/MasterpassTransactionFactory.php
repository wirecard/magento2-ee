<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Request;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use Magento\Store\Model\StoreManagerInterface;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Exception\MandatoryFieldMissingException;
use Wirecard\PaymentSdk\Transaction\MasterpassTransaction;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * Class MasterpassTransactionFactory
 * @package Wirecard\ElasticEngine\Gateway\Request
 */
class MasterpassTransactionFactory extends TransactionFactory
{
    const REFUND_OPERATION = Operation::CANCEL;
    /**
     * @var MasterpassTransaction
     */
    protected $transaction;

    /**
     * MasterpassTransactionFactory constructor.
     * @param UrlInterface $urlBuilder
     * @param ResolverInterface $resolver
     * @param StoreManagerInterface $storeManager
     * @param Transaction $transaction
     * @param BasketFactory $basketFactory
     * @param AccountHolderFactory $accountHolderFactory
     * @param ConfigInterface $methodConfig
     * @param Repository $transactionRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ResolverInterface $resolver,
        StoreManagerInterface $storeManager,
        Transaction $transaction,
        BasketFactory $basketFactory,
        AccountHolderFactory $accountHolderFactory,
        ConfigInterface $methodConfig,
        Repository $transactionRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder
    ) {
        parent::__construct(
            $urlBuilder,
            $resolver,
            $transaction,
            $methodConfig,
            $storeManager,
            $accountHolderFactory,
            $basketFactory,
            $transactionRepository,
            $searchCriteriaBuilder,
            $filterBuilder
        );
    }

    /**
     * @param array $commandSubject
     * @return MasterpassTransaction
     * @throws \InvalidArgumentException
     * @throws MandatoryFieldMissingException
     */
    public function create($commandSubject)
    {
        parent::create($commandSubject);

        /** @var PaymentDataObjectInterface $payment */

        $payment = $commandSubject[self::PAYMENT];
        $order = $payment->getOrder();
        $billingAddress = $order->getBillingAddress();

        $accountHolder = new AccountHolder();
        $accountHolder->setFirstName($billingAddress->getFirstname());
        $accountHolder->setLastName($billingAddress->getLastname());

        $this->transaction->setAccountHolder($accountHolder);

        return $this->transaction;
    }

    /**
     * @param array $commandSubject
     * @return MasterpassTransaction
     * @throws \InvalidArgumentException
     * @throws MandatoryFieldMissingException
     */
    public function capture($commandSubject)
    {
        parent::capture($commandSubject);

        /** @var PaymentDataObjectInterface $paymentDo */
        $paymentDo = $commandSubject[self::PAYMENT];

        /** @var OrderAdapterInterface $order */
        $order = $paymentDo->getOrder();

        /** @var Payment $payment */
        $payment = $paymentDo->getPayment();

        $transactions = $this->getTransactionsForOrder($order, $payment);
        $filteredTransactions = array_filter($transactions['items'], function ($tx) {
            if (!key_exists('payment-methods.0.name', $tx)) {
                return false;
            }

            return $tx['payment-methods.0.name'] === 'creditcard';
        });
        $creditCardTransaction = reset($filteredTransactions);

        $this->transaction->setParentTransactionId($creditCardTransaction['parent-transaction-id']);

        return $this->transaction;
    }

    /**
     * @param array $commandSubject
     * @return Transaction
     * @throws \InvalidArgumentException
     * @throws MandatoryFieldMissingException
     */
    public function refund($commandSubject)
    {
        parent::refund($commandSubject);

        $this->transaction->setParentTransactionId($this->transactionId);

        return $this->transaction;
    }

    public function void($commandSubject)
    {
        parent::void($commandSubject);

        return $this->transaction;
    }

    /**
     * @return string
     */
    public function getRefundOperation()
    {
        return self::REFUND_OPERATION;
    }
}
