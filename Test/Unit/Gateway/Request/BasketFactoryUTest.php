<?php

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Request;

use InvalidArgumentException;
use Magento\Checkout\Model\Session;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
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

    private $orderFactory;

    private $shippingAddress;

    private $quote;

    private $orderObject;

    public function setUp()
    {
        $this->order = $this->getMockBuilder(OrderAdapterInterface::class)->disableOriginalConstructor()->getMock();
        $item = $this->getMockBuilder(OrderItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrigData', 'getQtyInvoiced'])
            ->getMockForAbstractClass();
        $item->method('getPriceInclTax')->willReturn(1.0);
        $item->method('getOrigData')->willReturn(1);
        $item->method('getQtyInvoiced')->willReturn(2);
        $item->method('getDiscountInvoiced')->willReturn(2.0);
        $item->method('getBaseRowInvoiced')->willReturn(1);
        $item->method('getQtyRefunded')->willReturn(2);
        $item->method('getDiscountRefunded')->willReturn(2.0);
        $item->method('getBaseAmountRefunded')->willReturn(1);
        $zeroItem = $this->getMockBuilder(OrderItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrigData', 'getQtyInvoiced'])
            ->getMockForAbstractClass();
        $zeroItem->method('getPriceInclTax')->willReturn(0.0);
        $zeroItem->method('getOrigData')->willReturn(0);
        $zeroItem->method('getQtyInvoiced')->willReturn(0);
        $zeroItem->method('getDiscountInvoiced')->willReturn(0);
        $zeroItem->method('getQtyRefunded')->willReturn(0);
        $zeroItem->method('getDiscountRefunded')->willReturn(0);
        $zeroItem->method('getBaseAmountRefunded')->willReturn(0);
        $this->order->method('getItems')->willReturn([
            $item, $zeroItem
        ]);
        $this->order->method('getCurrencyCode')->willReturn('EUR');

        $this->itemFactory = $this->getMockBuilder(ItemFactory::class)->getMock();
        $this->itemFactory->method('create')->willReturn(new Item('', new Amount(0.0, 'EUR'), ''));
        $this->itemFactory->method('capture')->willReturn(new Item('', new Amount(0.0, 'EUR'), ''));
        $this->itemFactory->method('refund')->willReturn(new Item('', new Amount(0.0, 'EUR'), ''));

        $this->orderFactory = $this->getMockBuilder(OrderFactory::class)->disableOriginalConstructor()
            ->setMethods(['create'])->getMock();

        $this->orderObject = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $this->shippingAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingInclTax', 'getShippingDescription', 'getShippingMethod', 'getShippingTaxAmount', 'getDiscountAmount'])
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transaction = $this->getMockBuilder(Transaction::class)->getMock();
    }

    public function setUpWithQuoteData()
    {
        $this->shippingAddress->method('getShippingInclTax')->willReturn(5.0);
        $this->shippingAddress->method('getShippingDescription')->willReturn('Fixed Flat Rate');
        $this->shippingAddress->method('getShippingMethod')->willReturn('flatrate_flatrate');
        $this->shippingAddress->method('getShippingTaxAmount')->willReturn(0.0);
        $this->shippingAddress->method('getDiscountAmount')->willReturn(-1.0);

        $this->quote->method('getShippingAddress')->willReturn($this->shippingAddress);
        $this->checkoutSession->method('getQuote')->willReturn($this->quote);
    }

    public function setUpWithoutQuoteData()
    {
        $this->shippingAddress->method('getShippingInclTax')->willReturn(false);

        $this->orderObject = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $this->orderObject->method('getShippingInclTax')->willReturn(5.0);
        $this->orderObject->method('getShippingDescription')->willReturn('Fixed Flat Rate');
        $this->orderObject->method('getShippingMethod')->willReturn('flatrate_flatrate');
        $this->orderObject->method('getShippingTaxAmount')->willReturn(0.0);
        $this->orderObject->method('getShippingInvoiced')->willReturn(5.0);
        $this->orderObject->method('getShippingRefunded')->willReturn(5.0);

        $this->orderFactory->method('create')->willReturn($this->orderObject);

        $this->quote->method('getShippingAddress')->willReturn($this->shippingAddress);
        $this->checkoutSession->method('getQuote')->willReturn($this->quote);
    }

    public function testCreate()
    {
        $this->setUpWithQuoteData();
        $basketFactory = new BasketFactory($this->itemFactory, $this->checkoutSession, $this->orderFactory);

        $expected = new Basket();
        $expected->setVersion($this->transaction);
        $expected->add(new Item('', new Amount(0.0, 'EUR'), ''));

        $shipping = new Item('Shipping', new Amount(5.0, 'EUR'), 1);
        $shipping->setDescription('Fixed Flat Rate');
        $shipping->setArticleNumber('flatrate_flatrate');
        $shipping->setTaxRate(0.00);
        $expected->add($shipping);

        $this->assertEquals($expected, $basketFactory->create($this->order, $this->transaction));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateThrowsException()
    {
        $basketFactory = new BasketFactory($this->itemFactory, $this->checkoutSession, $this->orderFactory);
        $basketFactory->create(null, null);
    }

    public function testCapture()
    {
        $this->setUpWithoutQuoteData();

        $basketFactory = new BasketFactory($this->itemFactory, $this->checkoutSession, $this->orderFactory);

        $expected = new Basket();
        $expected->setVersion($this->transaction);
        $expected->add(new Item('', new Amount(0.0, 'EUR'), ''));

        $shipping = new Item('Shipping', new Amount(5.0, 'EUR'), 1);
        $shipping->setDescription('Fixed Flat Rate');
        $shipping->setArticleNumber('flatrate_flatrate');
        $shipping->setTaxRate(0.00);
        $expected->add($shipping);

        $this->assertEquals($expected, $basketFactory->capture($this->order, $this->transaction));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCaptureThrowsException()
    {
        $basketFactory = new BasketFactory($this->itemFactory, $this->checkoutSession, $this->orderFactory);
        $basketFactory->capture(null, null);
    }

    /**
     * @expectedException Magento\Framework\Exception\NoSuchEntityException
     */
    public function testCaptureThrowsNoOrderException()
    {
        $this->setUpWithQuoteData();
        $basketFactory = new BasketFactory($this->itemFactory, $this->checkoutSession, $this->orderFactory);
        $basketFactory->capture($this->order, $this->transaction);
    }

    public function testRefund()
    {
        $this->setUpWithoutQuoteData();

        $basketFactory = new BasketFactory($this->itemFactory, $this->checkoutSession, $this->orderFactory);

        $expected = new Basket();
        $expected->setVersion($this->transaction);
        $expected->add(new Item('', new Amount(0.0, 'EUR'), ''));

        $shipping = new Item('Shipping', new Amount(5.0, 'EUR'), 1);
        $shipping->setDescription('Fixed Flat Rate');
        $shipping->setArticleNumber('flatrate_flatrate');
        $shipping->setTaxRate(0.00);
        $expected->add($shipping);

        $this->assertEquals($expected, $basketFactory->refund($this->order, $this->transaction));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRefundThrowsException()
    {
        $basketFactory = new BasketFactory($this->itemFactory, $this->checkoutSession, $this->orderFactory);
        $basketFactory->refund(null, null);
    }

    /**
     * @expectedException Magento\Framework\Exception\NoSuchEntityException
     */
    public function testRefundThrowsNoOrderException()
    {
        $this->setUpWithQuoteData();
        $basketFactory = new BasketFactory($this->itemFactory, $this->checkoutSession, $this->orderFactory);
        $basketFactory->refund($this->order, $this->transaction);
    }
}
