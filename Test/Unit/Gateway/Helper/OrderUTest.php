<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Model;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Wirecard\ElasticEngine\Gateway\Helper;

class OrderUTest extends \PHPUnit_Framework_TestCase
{
    public static $ORDER_ID = '23233';

    /**
     * @var OrderRepositoryInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderRepository;

    /**
     * @var SearchCriteriaBuilder|PHPUnit_Framework_MockObject_MockObject
     */
    protected $searchCriteriaBuilder;

    /**
     * @var OrderSearchResultInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderSearchResult;

    /**
     * @var OrderInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderData;

    /**
     * @var Helper\Order|PHPUnit_Framework_MockObject_MockObject
     */
    protected $helper;

    public function setup()
    {
        $this->orderRepository = $this->getMock(OrderRepositoryInterface::class);

        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilder->method('create')->willReturn($searchCriteria);
        $this->searchCriteriaBuilder->method('addFilter')
            ->with(OrderInterface::INCREMENT_ID, self::$ORDER_ID)
            ->willReturn($this->searchCriteriaBuilder);

        $this->orderSearchResult = $this->getMockBuilder(OrderSearchResultInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderRepository->method('getList')->willReturn($this->orderSearchResult);

        $this->orderData = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new Helper\Order($this->orderRepository, $this->searchCriteriaBuilder);
    }

    public function testGetOrderByIncrementId()
    {
        $this->orderSearchResult->method('getItems')->willReturn([
            $this->orderData
        ]);

        $order = $this->helper->getOrderByIncrementId(self::$ORDER_ID);

        $this->assertSame($this->orderData, $order);
    }

    public function testGetOrderByIncrementIdNotFound()
    {
        $this->orderSearchResult->method('getItems')->willReturn([]);

        $this->expectException(NoSuchEntityException::class);

        $this->helper->getOrderByIncrementId(self::$ORDER_ID);
    }
}
