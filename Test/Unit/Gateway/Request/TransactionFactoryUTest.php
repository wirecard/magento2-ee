<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
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
        $this->order->method('getGrandTotalAmount')->willReturn('1.0');
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
        $transactionFactory = new TransactionFactory($this->urlBuilder, $this->resolver, new PayPalTransaction(),
            $this->config, $this->storeManager, $this->accountHolderFactory, $this->basketFactory);
        $transactionFactory->create([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRefundThrowsExceptionWithoutPayment()
    {
        $transactionFactory = new TransactionFactory($this->urlBuilder, $this->resolver, new PayPalTransaction(),
            $this->config, $this->storeManager, $this->accountHolderFactory, $this->basketFactory);
        $transactionFactory->refund([]);
    }

    public function testRefundOperationSetter()
    {
        $transactionFactory = new TransactionFactory($this->urlBuilder, $this->resolver, new PayPalTransaction(),
            $this->config, $this->storeManager, $this->accountHolderFactory, $this->basketFactory);
        $expected = Operation::CREDIT;
        $this->assertEquals($expected, $transactionFactory->getRefundOperation());
    }

    public function testCreateSetsAmountValues()
    {
        $transactionMock = $this->getMock(Transaction::class);
        $transactionMock->expects($this->Once())->method('setAmount')->with($this->equalTo(new Amount('1.0', 'EUR')));

        $transactionFactory = new TransactionFactory($this->urlBuilder, $this->resolver, $transactionMock,
            $this->config, $this->storeManager, $this->accountHolderFactory, $this->basketFactory);
        $transactionFactory->create($this->commandSubject);
    }

    public function testCreateSetsRedirect()
    {
        $transactionMock = $this->getMock(Transaction::class);
        $redirect = new Redirect('http://magen.to/frontend/redirect', 'http://magen.to/frontend/cancel', 'http://magen.to/frontend/redirect');
        $transactionMock->expects($this->Once())->method('setRedirect')->with($this->equalTo($redirect));

        $transactionFactory = new TransactionFactory($this->urlBuilder, $this->resolver, $transactionMock,
            $this->config, $this->storeManager, $this->accountHolderFactory, $this->basketFactory);
        $transactionFactory->create($this->commandSubject);
    }

    public function testCreateSetsNotification()
    {
        $transactionMock = $this->getMock(Transaction::class);
        $transactionMock->expects($this->Once())->method('setNotificationUrl')->with($this->equalTo('http://magen.to/frontend/notify'));

        $transactionFactory = new TransactionFactory($this->urlBuilder, $this->resolver, $transactionMock,
            $this->config, $this->storeManager, $this->accountHolderFactory, $this->basketFactory);
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

        $transactionFactory = new TransactionFactory($this->urlBuilder, $this->resolver, $transactionMock,
            $this->config, $this->storeManager, $this->accountHolderFactory, $this->basketFactory);

        $transactionFactory->create($this->commandSubject);
    }
}
