<?php

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Request;

use InvalidArgumentException;
use Magento\Sales\Api\Data\OrderItemInterface;
use Wirecard\ElasticEngine\Gateway\Request\ItemFactory;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Item;

class ItemFactoryUTest extends \PHPUnit_Framework_TestCase
{
    const DESCRIPTION = 'The brand new one plus 5';

    const SKU = '1815441151';

    private $orderItem;

    private $item;

    public function setUp()
    {
        $this->orderItem = $this->getMockBuilder(OrderItemInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderItem->method('getName')->willReturn('One Plus 5');
        $this->orderItem->method('getPriceInclTax')->willReturn(120.0);
        $this->orderItem->method('getQtyOrdered')->willReturn(1);
        $this->orderItem->method('getDescription')->willReturn(self::DESCRIPTION);
        $this->orderItem->method('getSku')->willReturn(self::SKU);
        $this->orderItem->method('getTaxAmount')->willReturn(20.00);

        $this->item = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->item->method('getName')->willReturn('One Plus 5');
        $this->item->method('getBaseRowInvoiced')->willReturn(120.0);
        $this->item->method('getBaseAmountRefunded')->willReturn(120.0);
        $this->item->method('getQtyInvoiced')->willReturn(1);
        $this->item->method('getQtyRefunded')->willReturn(1);
        $this->item->method('getDescription')->willReturn(self::DESCRIPTION);
        $this->item->method('getSku')->willReturn(self::SKU);
        $this->item->method('getTaxInvoiced')->willReturn(20.00);
        $this->item->method('getTaxRefunded')->willReturn(20.00);
    }

    public function testCreate()
    {
        $this->orderItem->method('getBaseRowTotalInclTax')->willReturn(120.00);
        $itemFactory = new ItemFactory();

        $expected = new Item('One Plus 5', new Amount(120.0, 'EUR'), 1);
        $expected->setDescription(self::DESCRIPTION);
        $expected->setArticleNumber(self::SKU);
        $expected->setTaxRate(number_format((100 * 20 / 120), 2));

        $this->assertEquals($expected, $itemFactory->create($this->orderItem, 'EUR'));
    }

    public function testCreateRoundingIssue()
    {
        $this->orderItem->method('getBaseRowTotalInclTax')->willReturn(100.00);
        $itemFactory = new ItemFactory();

        $expected = new Item('One Plus 5 x1', new Amount(100.0, 'EUR'), 1);
        $expected->setDescription(self::DESCRIPTION);
        $expected->setArticleNumber(self::SKU);
        $expected->setTaxRate(number_format((100 * 20 / 100), 2));

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

    public function testCapture()
    {
        $this->item->method('getBaseRowInvoiced')->willReturn(120.00);
        $itemFactory = new ItemFactory();

        $expected = new Item('One Plus 5', new Amount(120.0, 'EUR'), 1);
        $expected->setDescription(self::DESCRIPTION);
        $expected->setArticleNumber(self::SKU);
        $expected->setTaxRate(number_format((100 * 20 / 120), 2));

        $this->assertEquals($expected, $itemFactory->capture($this->item, 'EUR', 1));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCaptureThrowsException()
    {
        $itemFactory = new ItemFactory();
        $itemFactory->capture(null, 'EUR', 1);
    }

    public function testRefund()
    {
        $this->item->method('getBaseAmountRefunded')->willReturn(120.00);
        $itemFactory = new ItemFactory();

        $expected = new Item('One Plus 5', new Amount(120.0, 'EUR'), 1);
        $expected->setDescription(self::DESCRIPTION);
        $expected->setArticleNumber(self::SKU);
        $expected->setTaxRate(number_format((100 * 20 / 120), 2));

        $this->assertEquals($expected, $itemFactory->refund($this->item, 'EUR', 1));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRefundThrowsException()
    {
        $itemFactory = new ItemFactory();
        $itemFactory->refund(null, 'EUR', 1);
    }
}
