<?php

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Request;

use InvalidArgumentException;
use Magento\Sales\Api\Data\OrderItemInterface;
use Wirecard\ElasticEngine\Gateway\Request\ItemFactory;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Item;

class ItemFactoryUTest extends \PHPUnit_Framework_TestCase
{
    private $orderItem;

    public function setUp()
    {
        $this->orderItem = $this->getMockBuilder(OrderItemInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderItem->method('getName')->willReturn('One Plus 5');
        $this->orderItem->method('getPriceInclTax')->willReturn(120.0);
        $this->orderItem->method('getQtyOrdered')->willReturn(1);
        $this->orderItem->method('getDescription')->willReturn('The brand new one plus 5');
        $this->orderItem->method('getSku')->willReturn('1815441151');
        $this->orderItem->method('getTaxAmount')->willReturn(20.00);

    }

    public function testCreate()
    {
        $this->orderItem->method('getBaseRowTotalInclTax')->willReturn(120.00);
        $itemFactory = new ItemFactory();

        $expected = new Item('One Plus 5', new Amount(120.0, 'EUR'), 1);
        $expected->setDescription('The brand new one plus 5');
        $expected->setArticleNumber('1815441151');
        $expected->setTaxAmount(new Amount(20.0, 'EUR'));

        $this->assertEquals($expected, $itemFactory->create($this->orderItem, 'EUR'));
    }

    public function testCreateRoundingIssue()
    {
        $this->orderItem->method('getBaseRowTotalInclTax')->willReturn(100.00);
        $itemFactory = new ItemFactory();

        $expected = new Item('One Plus 5 x1', new Amount(100.0, 'EUR'), 1);
        $expected->setDescription('The brand new one plus 5');
        $expected->setArticleNumber('1815441151');
        $expected->setTaxAmount(new Amount(20.0, 'EUR'));

        $this->assertEquals($expected, $itemFactory->create($this->orderItem, 'EUR'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateThrowsException()
    {
        $itemFactory = new ItemFactory();
        $itemFactory->create(null, 'EUR');
    }
}
