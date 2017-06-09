<?php

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Wirecard\ElasticEngine\Gateway\Request\BasketFactory;
use Wirecard\ElasticEngine\Gateway\Request\ItemFactory;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\Item;

class BasketFactoryUTest extends \PHPUnit_Framework_TestCase
{
    private $order;

    private $itemFactory;

    public function setUp()
    {
        $this->order = $this->getMockBuilder(OrderAdapterInterface::class)->disableOriginalConstructor()->getMock();
        $this->order->method('getItems')->willReturn([
            'item 1'
        ]);
        $this->order->method('getCurrencyCode')->willReturn('EUR');

        $this->itemFactory = $this->getMockBuilder(ItemFactory::class)->getMock();
        $this->itemFactory->method('create')->willReturn(new Item('',new Amount(0.0, 'EUR'),''));
    }

    public function testCreate()
    {
        $basketFactory = new BasketFactory($this->itemFactory);

        $expected = new Basket();
        $expected->add(new Item('',new Amount(0.0, 'EUR'),''));

        $this->assertEquals($expected, $basketFactory->create($this->order));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateThrowsException()
    {
        $basketFactory = new BasketFactory($this->itemFactory);
        $basketFactory->create(null);
    }
}
