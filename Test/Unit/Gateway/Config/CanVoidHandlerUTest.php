<?php
/**
 * Created by IntelliJ IDEA.
 * User: tomaz.puhar
 * Date: 10.01.2018
 * Time: 15:29
 */

namespace Unit\Gateway\Config;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\TransactionExtensionInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection;
use Wirecard\ElasticEngine\Gateway\Config\CanVoidHandler;

class CanVoidHandlerUTest extends \PHPUnit_Framework_TestCase
{
    private $canVoidHandler;

    private $canNotVoidHandler;

    private $transaction;

    private $notAuthTransaction;

    private $payment;

    public function setUp()
    {
        $filter = $this->getMockBuilder(Filter::class)->disableOriginalConstructor()->getMock();
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)->disableOriginalConstructor()->getMock();
        $transactionList = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()->getMock();
        $transactionList->method('getAllIds')->willReturn([1, 2]);
        $invalidTransactionList = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()->getMock();
        $invalidTransactionList->method('getAllIds')->willReturn([1, 2]);

        $searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)->disableOriginalConstructor()
            ->getMock();
        $searchCriteriaBuilder->method('addFilter')->willReturn($searchCriteriaBuilder);
        $searchCriteriaBuilder->method('addSortOrder')->willReturn($searchCriteriaBuilder);
        $searchCriteriaBuilder->method('create')->willReturn($searchCriteria);

        $transactionExtensionInterface = $this->getMockBuilder(TransactionExtensionInterface::class)->disableOriginalConstructor()->getMock();
        $this->transaction = $this->getMockBuilder(Transaction::class)->disableOriginalConstructor()->getMock();
        $this->notAuthTransaction = $this->getMockBuilder(Transaction::class)->disableOriginalConstructor()->getMock();

        $this->transaction->method('getTxnType')->willReturn('authorization');
        $this->transaction->method('setExtensionAttributes')->with($transactionExtensionInterface)->willReturn($this->transaction);

        $this->notAuthTransaction->method('getTxnType')->willReturn('capture');
        $this->notAuthTransaction->method('setExtensionAttributes')->with($transactionExtensionInterface)->willReturn($this->notAuthTransaction);

        $transactions = [$this->transaction];
        $transactionList->method('getLastItem')->willReturn($this->transaction);
        $transactionList->method('getItems')->willReturn($transactions);
        $transactionList->method('getItemById')->willReturn($this->transaction);
        $transactionRepository = $this->getMockBuilder(Transaction\Repository::class)->disableOriginalConstructor()->getMock();
        $transactionRepository->method('getList')->willReturn($transactionList);

        $filterBuilder = $this->getMockBuilder(FilterBuilder::class)->disableOriginalConstructor()->getMock();
        $filterBuilder->method('setField')->willReturn($filterBuilder);
        $filterBuilder->method('setValue')->willReturn($filterBuilder);
        $filterBuilder->method('create')->willReturn($filter);

        $objectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();

        $this->canVoidHandler = new CanVoidHandler($objectManager, $transactionRepository, $searchCriteriaBuilder, $filterBuilder);

        $invalidTransactionList->method('getLastItem')->willReturn($this->notAuthTransaction);
        $invalidTransactionList->method('getItems')->willReturn([$this->notAuthTransaction]);
        $invalidTransactionList->method('getItemById')->willReturn($this->notAuthTransaction);
        $transactionRepository = $this->getMockBuilder(Transaction\Repository::class)->disableOriginalConstructor()->getMock();
        $transactionRepository->method('getList')->willReturn($invalidTransactionList);

        $this->canNotVoidHandler = new CanVoidHandler($objectManager, $transactionRepository, $searchCriteriaBuilder, $filterBuilder);

        $orderAdapterInterface = $this->getMockBuilder(OrderAdapterInterface::class)->disableOriginalConstructor()->getMock();
        $orderAdapterInterface->method('getId')->willReturn(1);

        $this->payment = $this->getMockBuilder(PaymentDataObjectInterface::class)->disableOriginalConstructor()->getMock();
        $this->payment->method('getOrder')->willReturn($orderAdapterInterface);
    }

    public function testHandle()
    {
        $orderPayment = $this->getMockBuilder(OrderPaymentInterface::class)->disableOriginalConstructor()->getMock();
        $orderPayment->method('getAmountPaid')->willReturn(1.0);
        $orderPayment->method('getAmountOrdered')->willReturn(2.0);
        $this->payment->method('getPayment')->willReturn($orderPayment);
        $this->canVoidHandler->handle(['payment' => $this->payment]);
    }

    public function testFailedHandle()
    {
        $orderPayment = $this->getMockBuilder(OrderPaymentInterface::class)->disableOriginalConstructor()->getMock();
        $orderPayment->method('getAmountPaid')->willReturn(1.0);
        $orderPayment->method('getAmountOrdered')->willReturn(2.0);
        $this->payment->method('getPayment')->willReturn($orderPayment);
        $this->canNotVoidHandler->handle(['payment' => $this->payment]);
    }
}
