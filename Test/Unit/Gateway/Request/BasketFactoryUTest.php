<?php

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Request;

use InvalidArgumentException;
use Magento\Checkout\Model\Session;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Wirecard\ElasticEngine\Gateway\Request\BasketFactory;
use Wirecard\ElasticEngine\Gateway\Request\ItemFactory;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\Item;
use Wirecard\PaymentSdk\Transaction\Transaction;

class BasketFactoryUTest extends \PHPUnit_Framework_TestCase
{
    private $order;

    private $itemFactory;

    private $checkoutSession;

    private $transaction;

    public function setUp()
    {
        $this->order = $this->getMockBuilder(OrderAdapterInterface::class)->disableOriginalConstructor()->getMock();
        $this->order->method('getItems')->willReturn([
            'item 1'
        ]);
        $this->order->method('getCurrencyCode')->willReturn('EUR');

        $this->itemFactory = $this->getMockBuilder(ItemFactory::class)->getMock();
        $this->itemFactory->method('create')->willReturn(new Item('', new Amount(0.0, 'EUR'), ''));

        $shippingAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingInclTax', 'getShippingDescription', 'getShippingMethod', 'getShippingTaxAmount'])
            ->getMock();
        $shippingAddress->method('getShippingInclTax')->willReturn(5.0);
        $shippingAddress->method('getShippingDescription')->willReturn('Fixed Flat Rate');
        $shippingAddress->method('getShippingMethod')->willReturn('flatrate_flatrate');
        $shippingAddress->method('getShippingTaxAmount')->willReturn(0.0);

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote->method('getShippingAddress')->willReturn($shippingAddress);

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->transaction = $this->getMockBuilder(Transaction::class)->getMock();
    }

    public function testCreate()
    {
        $basketFactory = new BasketFactory($this->itemFactory, $this->checkoutSession);

        $expected = new Basket();
        $expected->setVersion($this->transaction);
        $expected->add(new Item('', new Amount(0.0, 'EUR'), ''));

        $shipping = new Item('Shipping', new Amount(5.0, 'EUR'), 1);
        $shipping->setDescription('Fixed Flat Rate');
        $shipping->setArticleNumber('flatrate_flatrate');
        $shipping->setTaxRate(0.0);
        $expected->add($shipping);

        $this->assertEquals($expected, $basketFactory->create($this->order, $this->transaction));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateThrowsException()
    {
        $basketFactory = new BasketFactory($this->itemFactory, $this->checkoutSession);
        $basketFactory->create(null, null);
    }
}
