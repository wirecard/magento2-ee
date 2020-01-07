<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Request;

use InvalidArgumentException;
use Magento\Bundle\Model\Product\Price;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Checkout\Model\Session;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
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
    /** @var \PHPUnit_Framework_MockObject_MockObject|OrderAdapterInterface */
    private $order;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ItemFactory */
    private $itemFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Session */
    private $checkoutSession;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Transaction */
    private $transaction;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OrderFactory */
    private $orderFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OrderRepositoryInterface */
    private $orderRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Address */
    private $shippingAddress;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Quote */
    private $quote;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Order */
    private $orderObject;

    public function setUp()
    {
        $this->order = $this->getMockBuilder(OrderAdapterInterface::class)->disableOriginalConstructor()->getMock();
        $item        = $this->getOrderItemMock();
        $item->method('getName')->willReturn('item');

        $zeroItem = $this->getOrderItemMock(0, 0, 0, 0, 0, 0, 0, 0);

        // bundle product with dynamic pricing, only bundledItem must be added to the basket
        $bundleDynamic = $this->getOrderItemMock(100.0, 0, 0, 0, 0, 0, 0, 0, Type::TYPE_BUNDLE);
        $bundleDynamic->method('getName')->willReturn('bundledDynamic');

        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPriceType'])
            ->getMock();
        $product->method('getPriceType')->willReturn(Price::PRICE_TYPE_DYNAMIC);

        $bundleDynamic->method('getProduct')->willReturn($product);

        $bundledItem1 = $this->getOrderItemMock();
        $bundledItem1->method('getName')->willReturn('bundledItem1');
        $bundledItem1->method('getParentItem')->willReturn($bundleDynamic);

        // bundle product with fixed pricing, only the bundleItem must be added to the basket
        $bundleFixed = $this->getOrderItemMock(100.0, 1, 2, 2.0, 1, 2, 2.0, 1, Type::TYPE_BUNDLE);
        $bundleFixed->method('getName')->willReturn('bundleFixed');

        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPriceType'])
            ->getMock();
        $product->method('getPriceType')->willReturn(Price::PRICE_TYPE_FIXED);

        $bundleFixed->method('getProduct')->willReturn($product);

        $bundledItem2 = $this->getOrderItemMock();
        $bundledItem2->method('getParentItem')->willReturn($bundleFixed);
        $bundledItem2->method('getName')->willReturn('bundledItem2');

        // must not be added to the basket
        $itemWithParent = $this->getOrderItemMock();
        $itemWithParent->method('getParentItem')->willReturn($item);

        $this->order->method('getItems')->willReturn([
            $item,
            $zeroItem,
            $bundleDynamic,
            $bundledItem1,
            $bundleFixed,
            $bundledItem2,
            $itemWithParent
        ]);
        $this->order->method('getCurrencyCode')->willReturn('EUR');

        $this->itemFactory = $this->getMockBuilder(ItemFactory::class)->getMock();

        $this->itemFactory->method('capture')->willReturn(new Item('', new Amount(0.0, 'EUR'), 0));
        $this->itemFactory->method('refund')->willReturn(new Item('', new Amount(0.0, 'EUR'), 0));

        $this->orderFactory = $this->getMockBuilder(OrderFactory::class)->disableOriginalConstructor()
            ->setMethods(['create'])->getMock();

        $this->orderObject = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();

        $this->orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)->disableOriginalConstructor()
            ->setMethods(['get', 'getList', 'delete', 'save'])->getMock();

        $this->shippingAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getShippingInclTax',
                'getBaseShippingInclTax',
                'getShippingDescription',
                'getShippingMethod',
                'getShippingTaxAmount',
                'getBaseShippingTaxAmount',
                'getDiscountAmount',
                'getBaseDiscountAmount'
            ])
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transaction = $this->getMockBuilder(Transaction::class)->getMock();
    }

    protected function getOrderItemMock(
        $priceInc = 1.0,
        $origData = 1,
        $qtyInvoice = 2,
        $discountInvoices = 2.0,
        $baseRowInvoiced = 1,
        $qtyRefunded = 2,
        $discountRefunded = 2.0,
        $baseAmountRefunded = 1,
        $type = Type::TYPE_SIMPLE
    ) {
        $item = $this->getMockBuilder(OrderItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrigData', 'getQtyInvoiced', 'getProduct', 'getName'])
            ->getMockForAbstractClass();
        $item->method('getPriceInclTax')->willReturn($priceInc);
        $item->method('getBasePriceInclTax')->willReturn($priceInc);
        $item->method('getOrigData')->willReturn($origData);
        $item->method('getQtyInvoiced')->willReturn($qtyInvoice);
        $item->method('getDiscountInvoiced')->willReturn($discountInvoices);
        $item->method('getBaseRowInvoiced')->willReturn($baseRowInvoiced);
        $item->method('getQtyRefunded')->willReturn($qtyRefunded);
        $item->method('getDiscountRefunded')->willReturn($discountRefunded);
        $item->method('getBaseAmountRefunded')->willReturn($baseAmountRefunded);
        $item->method('getProductType')->willReturn($type);

        return $item;
    }

    public function setUpWithQuoteData()
    {
        $this->shippingAddress->method('getBaseShippingInclTax')->willReturn(5.0);
        $this->shippingAddress->method('getShippingDescription')->willReturn('Fixed Flat Rate');
        $this->shippingAddress->method('getShippingMethod')->willReturn('flatrate_flatrate');
        $this->shippingAddress->method('getBaseShippingTaxAmount')->willReturn(0.0);
        $this->shippingAddress->method('getBaseDiscountAmount')->willReturn(-1.0);

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

        $this->itemFactory->expects($this->at(0))->method('create')
            ->willReturn(new Item('item', new Amount(0.0, 'EUR'), 0));
        $this->itemFactory->expects($this->at(1))->method('create')
            ->willReturn(new Item('bundledItem1', new Amount(0.0, 'EUR'), 0));
        $this->itemFactory->expects($this->at(2))->method('create')
            ->willReturn(new Item('bundleFixed', new Amount(0.0, 'EUR'), 0));

        $basketFactory = new BasketFactory($this->itemFactory, $this->checkoutSession, $this->orderFactory, $this->orderRepository);

        $expected = new Basket();
        $expected->setVersion($this->transaction);
        $expected->add(new Item('item', new Amount(0.0, 'EUR'), 0));
        $expected->add(new Item('bundledItem1', new Amount(0.0, 'EUR'), 0));
        $expected->add(new Item('bundleFixed', new Amount(0.0, 'EUR'), 0));

        $shipping = new Item('Shipping', new Amount(5.0, 'EUR'), 1);
        $shipping->setDescription('Fixed Flat Rate');
        $shipping->setArticleNumber('flatrate_flatrate');
        $shipping->setTaxRate(0.00);
        $expected->add($shipping);
        $basket = $basketFactory->create($this->order, $this->transaction);
        $this->assertEquals($expected, $basket);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateThrowsException()
    {
        $basketFactory = new BasketFactory($this->itemFactory, $this->checkoutSession, $this->orderFactory, $this->orderRepository);
        $basketFactory->create(null, null);
    }

    public function testCapture()
    {
        $this->setUpWithoutQuoteData();

        $basketFactory = new BasketFactory($this->itemFactory, $this->checkoutSession, $this->orderFactory, $this->orderRepository);

        $expected = new Basket();
        $expected->setVersion($this->transaction);
        $expected->add(new Item('', new Amount(0.0, 'EUR'), 0));
        $expected->add(new Item('', new Amount(0.0, 'EUR'), 0));

        $shipping = new Item('Shipping', new Amount(5.0, 'EUR'), 1);
        $shipping->setDescription('Fixed Flat Rate');
        $shipping->setArticleNumber('flatrate_flatrate');
        $shipping->setTaxRate(0.00);
        $expected->add($shipping);
        $this->orderRepository->method('get')->willReturn($this->orderObject);

        $this->assertEquals($expected, $basketFactory->capture($this->order, $this->transaction));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCaptureThrowsException()
    {
        $basketFactory = new BasketFactory($this->itemFactory, $this->checkoutSession, $this->orderFactory, $this->orderRepository);
        $basketFactory->capture(null, null);
    }

    /**
     * @expectedException Magento\Framework\Exception\NoSuchEntityException
     */
    public function testCaptureThrowsNoOrderException()
    {
        $this->setUpWithQuoteData();
        $basketFactory = new BasketFactory($this->itemFactory, $this->checkoutSession, $this->orderFactory, $this->orderRepository);
        $basketFactory->capture($this->order, $this->transaction);
    }

    public function testRefund()
    {
        $this->setUpWithoutQuoteData();

        $basketFactory = new BasketFactory($this->itemFactory, $this->checkoutSession, $this->orderFactory, $this->orderRepository);

        $expected = new Basket();
        $expected->setVersion($this->transaction);
        $expected->add(new Item('', new Amount(0.0, 'EUR'), 0));
        $expected->add(new Item('', new Amount(0.0, 'EUR'), 0));

        $shipping = new Item('Shipping', new Amount(5.0, 'EUR'), 1);
        $shipping->setDescription('Fixed Flat Rate');
        $shipping->setArticleNumber('flatrate_flatrate');
        $shipping->setTaxRate(0.00);
        $expected->add($shipping);
        $this->orderRepository->method('get')->willReturn($this->orderObject);

        $this->assertEquals($expected, $basketFactory->refund($this->order, $this->transaction));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRefundThrowsException()
    {
        $basketFactory = new BasketFactory($this->itemFactory, $this->checkoutSession, $this->orderFactory, $this->orderRepository);
        $basketFactory->refund(null, null);
    }

    /**
     * @expectedException Magento\Framework\Exception\NoSuchEntityException
     */
    public function testRefundThrowsNoOrderException()
    {
        $this->setUpWithQuoteData();
        $basketFactory = new BasketFactory($this->itemFactory, $this->checkoutSession, $this->orderFactory, $this->orderRepository);
        $basketFactory->refund($this->order, $this->transaction);
    }
}
