<?php
/**
 * Created by IntelliJ IDEA.
 * User: jakub.polomsky
 * Date: 15. 11. 2017
 * Time: 11:36
 */

namespace Unit\Gateway\Config;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection;
use Wirecard\ElasticEngine\Gateway\Config\CanCaptureHandler;

class CanCaptureHandlerTest extends \PHPUnit_Framework_TestCase
{

    /** @var  CanCaptureHandler $canCaptureHandler */
    private $canCaptureHandler;

    private $transaction;

    private $payment;

    public function setUp()
    {
        $filter = $this->getMockBuilder(Filter::class)->disableOriginalConstructor()->getMock();
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)->disableOriginalConstructor()->getMock();
        $transactionList = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()->getMock();
        $transactionList->method('getAllIds')->willReturn([1, 2]);

        $searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)->disableOriginalConstructor()
            ->getMock();
        $searchCriteriaBuilder->method('addFilter')->willReturn($searchCriteriaBuilder);
        $searchCriteriaBuilder->method('create')->willReturn($searchCriteria);

        $transactionRepository = $this->getMockBuilder(Transaction\Repository::class)->disableOriginalConstructor()->getMock();
        $transactionRepository->method('getList')->willReturn($transactionList);

        $this->transaction = $this->getMockBuilder(Transaction::class)->disableOriginalConstructor()->getMock();
        $this->transaction->method('getTxnType')->willReturn('authorization');
        $transactionList->method('getItemById')->willReturn($this->transaction);

        $filterBuilder = $this->getMockBuilder(FilterBuilder::class)->disableOriginalConstructor()->getMock();
        $filterBuilder->method('setField')->willReturn($filterBuilder);
        $filterBuilder->method('setValue')->willReturn($filterBuilder);
        $filterBuilder->method('create')->willReturn($filter);

        $objectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();

        $this->canCaptureHandler = new CanCaptureHandler($objectManager, $transactionRepository, $searchCriteriaBuilder,
            $filterBuilder);

        $orderAdapterInterface = $this->getMockBuilder(OrderAdapterInterface::class)->disableOriginalConstructor()->getMock();
        $orderAdapterInterface->method('getId')->willReturn(1);

        $this->payment = $this->getMockBuilder(PaymentDataObjectInterface::class)->disableOriginalConstructor()->getMock();
        $this->payment->method('getOrder')->willReturn($orderAdapterInterface);
    }

    public function testHandle()
    {
        $this->canCaptureHandler->handle(['payment' => $this->payment]);
    }
}
