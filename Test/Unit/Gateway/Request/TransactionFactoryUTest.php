<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Request;

use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Wirecard\ElasticEngine\Gateway\Request\AccountHolderFactory;
use Wirecard\ElasticEngine\Gateway\Request\BasketFactory;
use Wirecard\ElasticEngine\Gateway\Request\TransactionFactory;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;

class TransactionFactoryUTest extends \PHPUnit_Framework_TestCase
{
    private $urlBuilder;

    private $resolver;

    private $payment;

    private $order;

    private $storeManager;

    private $basketFactory;

    private $accountHolderFactory;

    private $config;

    private $commandSubject;

    public function setUp()
    {
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();
        $this->urlBuilder->method('getRouteUrl')->willReturn('http://magen.to/');

        $this->resolver = $this->getMockBuilder(ResolverInterface::class)->disableOriginalConstructor()->getMock();

        $address = $this->getMockBuilder(AddressAdapterInterface::class)->disableOriginalConstructor()->getMock();
        $address->method('getEmail')->willReturn('test@example.com');
        $address->method('getFirstname')->willReturn('Joe');
        $address->method('getLastname')->willReturn('Doe');

        $this->order = $this->getMockBuilder(OrderAdapterInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->order->method('getGrandTotalAmount')->willReturn(1.0);
        $this->order->method('getCurrencyCode')->willReturn('EUR');
        $this->order->method('getId')->willReturn('1');
        $this->order->method('getShippingAddress')->willReturn($address);
        $this->payment = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->payment->method('getOrder')->willReturn($this->order);

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getName')->willReturn('My shop name');

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $this->storeManager->method('getStore')->willReturn($store);

        $this->basketFactory = $this->getMockBuilder(BasketFactory::class)->disableOriginalConstructor()->getMock();
        $this->basketFactory->method('create')->willReturn(new Basket());

        $this->accountHolderFactory = $this->getMockBuilder(AccountHolderFactory::class)->disableOriginalConstructor()->getMock();
        $this->accountHolderFactory->method('create')->willReturn(new AccountHolder());

        $this->config = $this->getMockBuilder(ConfigInterface::class)->disableOriginalConstructor()->getMock();

        $this->commandSubject = ['payment' => $this->payment];
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateThrowsExceptionWithoutPayment()
    {
        $transactionFactory = new TransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            new PayPalTransaction(),
            $this->config,
            $this->storeManager,
            $this->accountHolderFactory,
            $this->basketFactory
        );
        $transactionFactory->create([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRefundThrowsExceptionWithoutPayment()
    {
        $transactionFactory = new TransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            new PayPalTransaction(),
            $this->config,
            $this->storeManager,
            $this->accountHolderFactory,
            $this->basketFactory
        );
        $transactionFactory->refund([]);
    }

    public function testRefundOperationSetter()
    {
        $transactionFactory = new TransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            new PayPalTransaction(),
            $this->config,
            $this->storeManager,
            $this->accountHolderFactory,
            $this->basketFactory
        );
        $expected = Operation::CREDIT;
        $this->assertEquals($expected, $transactionFactory->getRefundOperation());
    }

    public function testCreateSetsAmountValues()
    {
        $transactionMock = $this->getMock(Transaction::class);
        $transactionMock->expects($this->Once())->method('setAmount')->with($this->equalTo(new Amount(1.0, 'EUR')));

        $transactionFactory = new TransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $transactionMock,
            $this->config,
            $this->storeManager,
            $this->accountHolderFactory,
            $this->basketFactory
        );
        $transactionFactory->create($this->commandSubject);
    }

    public function testCreateSetsRedirect()
    {
        $transactionMock = $this->getMock(Transaction::class);
        $transactionMock->method('getConfigKey')->willReturn('paypal');
        $redirect = new Redirect(
            'http://magen.to/frontend/redirect?method=paypal',
            'http://magen.to/frontend/cancel?method=paypal',
            'http://magen.to/frontend/redirect?method=paypal');
        $transactionMock->expects($this->Once())->method('setRedirect')->with($this->equalTo($redirect));

        $transactionFactory = new TransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $transactionMock,
            $this->config,
            $this->storeManager,
            $this->accountHolderFactory,
            $this->basketFactory
        );
        $transactionFactory->create($this->commandSubject);
    }

    public function testCreateWithAdditionalInformation()
    {
        $this->config->expects($this->at(0))->method('getValue')->willReturn(true);

        $transactionMock = $this->getMock(Transaction::class);
        $transactionMock->method('setDescriptor')->willReturn('Testshop');
        $transactionMock->method('setAccountHolder')->willReturn(new AccountHolder());
        $transactionMock->method('setShipping')->willReturn(new AccountHolder());
        $transactionMock->method('setOrderNumber')->willReturn('1');
        $transactionMock->method('setBasket')->willReturn(new Basket());
        $transactionMock->method('setIpAddress')->willReturn('127.0.0.1');
        $transactionMock->method('setConsumerId')->willReturn('1');

        $transactionFactory = new TransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $transactionMock,
            $this->config,
            $this->storeManager,
            $this->accountHolderFactory,
            $this->basketFactory
        );

        $transactionFactory->create($this->commandSubject);
    }
}
