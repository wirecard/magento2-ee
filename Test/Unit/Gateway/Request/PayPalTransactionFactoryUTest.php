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
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Wirecard\ElasticEngine\Gateway\Request\AccountHolderFactory;
use Wirecard\ElasticEngine\Gateway\Request\BasketFactory;
use Wirecard\ElasticEngine\Gateway\Request\PayPalTransactionFactory;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;

class PayPalTransactionFactoryUTest extends \PHPUnit_Framework_TestCase
{
    const ORDER_ID = '1234567';

    private $urlBuilder;

    private $resolver;

    private $storeManager;

    private $basketFactory;

    private $accountHolderFactory;

    private $config;

    private $payment;

    private $paymentDo;

    private $order;

    private $commandSubject;

    private $transaction;

    public function setUp()
    {
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();
        $this->urlBuilder->method('getRouteUrl')->willReturn('http://magen.to/');

        $this->resolver = $this->getMockBuilder(ResolverInterface::class)->disableOriginalConstructor()->getMock();
        $this->resolver->method('getLocale')->willReturn('en_US');

        $store = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $store->method('getName')->willReturn('My shop name');

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $this->storeManager->method('getStore')->willReturn($store);

        $this->basketFactory = $this->getMockBuilder(BasketFactory::class)->disableOriginalConstructor()->getMock();
        $this->basketFactory->method('create')->willReturn(new Basket());

        $this->accountHolderFactory = $this->getMockBuilder(AccountHolderFactory::class)->disableOriginalConstructor()->getMock();
        $this->accountHolderFactory->method('create')->willReturn(new AccountHolder());

        $this->config = $this->getMockBuilder(ConfigInterface::class)->disableOriginalConstructor()->getMock();

        $address = $this->getMockBuilder(AddressAdapterInterface::class)->disableOriginalConstructor()->getMock();
        $address->method('getEmail')->willReturn('test@example.com');
        $address->method('getFirstname')->willReturn('Joe');
        $address->method('getLastname')->willReturn('Doe');

        $this->order = $this->getMockBuilder(OrderAdapterInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->order->method('getOrderIncrementId')->willReturn(self::ORDER_ID);
        $this->order->method('getBillingAddress')->willReturn($address);
        $this->order->method('getShippingAddress')->willReturn($address);
        $this->order->method('getGrandTotalAmount')->willReturn(1.0);
        $this->order->method('getCurrencyCode')->willReturn('EUR');
        $this->payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $this->payment->method('getParentTransactionId')->willReturn('123456PARENT');
        $this->paymentDo = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->paymentDo->method('getOrder')->willReturn($this->order);
        $this->paymentDo->method('getPayment')->willReturn($this->payment);

        $this->commandSubject = ['payment' => $this->paymentDo, 'amount' => 1.0];

        $this->transaction = $this->getMockBuilder(Transaction::class)->disableOriginalConstructor()->getMock();
    }

    public function testRefundOperationSetter()
    {
        $transactionFactory = new PayPalTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            new PayPalTransaction(),
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config
        );
        $expected = Operation::CANCEL;
        $this->assertEquals($expected, $transactionFactory->getRefundOperation());
    }

    public function testCreateMinimum()
    {
        $transaction = new PayPalTransaction();
        $transactionFactory = new PayPalTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config
        );

        $expected = $this->minimumExpectedTransaction();

        $this->assertEquals($expected, $transactionFactory->create($this->commandSubject));
    }

    public function testVoidOperationMinimum()
    {
        $transaction = new PayPalTransaction();
        $transactionFactory = new PayPalTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config
        );

        $expected = $this->minimalRefundTransaction();

        $this->assertEquals($expected, $transactionFactory->void($this->commandSubject));
    }

    /**
     * @return PayPalTransaction
     */
    private function minimumExpectedTransaction()
    {
        $expected = new PayPalTransaction();
        $expected->setAccountHolder(new AccountHolder());
        $expected->setShipping(new AccountHolder());
        $expected->setOrderNumber(self::ORDER_ID);
        $expected->setOrderDetail('test@example.com Joe Doe');

        $expected->setAmount(new Amount(1.0, 'EUR'));
        $expected->setRedirect(new Redirect(
            'http://magen.to/frontend/redirect?method=paypal',
            'http://magen.to/frontend/cancel?method=paypal',
            'http://magen.to/frontend/redirect?method=paypal'
        ));

        $customFields = new CustomFieldCollection();
        $customFields->add(new CustomField('orderId', self::ORDER_ID));
        $expected->setCustomFields($customFields);
        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');

        return $expected;
    }

    /**
     * @return PayPalTransaction
     */
    private function minimalCaptureTransaction()
    {
        $expected = new PayPalTransaction();

        $expected->setParentTransactionId('123456PARENT');
        $expected->setAmount(new Amount(1.0, 'EUR'));

        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');

        return $expected;
    }

    /**
     * @return PayPalTransaction
     */
    private function minimalRefundTransaction()
    {
        $expected = new PayPalTransaction();

        $expected->setAmount(new Amount(1.0, 'EUR'));
        $expected->setParentTransactionId('123456PARENT');

        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');

        return $expected;
    }

    public function testCreateSetsBasket()
    {
        $this->config->expects($this->at(1))->method('getValue')->willReturn(true);

        $transaction = new PayPalTransaction();
        $transactionFactory = new PayPalTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config
        );

        $expected = $this->minimumExpectedTransaction();
        $expected->setBasket(new Basket());

        $this->assertEquals($expected, $transactionFactory->create($this->commandSubject));
    }

    public function testCapture()
    {
        $transaction = new PayPalTransaction();
        $transaction->setParentTransactionId('123456PARENT');

        $transactionFactory = new PayPalTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config
        );

        $this->assertEquals($this->minimalCaptureTransaction(), $transactionFactory->capture($this->commandSubject));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCaptureWithWrongCommandSubject()
    {
        $transaction = new PayPalTransaction();
        $transaction->setParentTransactionId('123456PARENT');

        $transactionFactory = new PayPalTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config
        );

        $this->assertEquals($this->minimalCaptureTransaction(), $transactionFactory->capture([]));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateWithWrongCommandSubject()
    {
        $transaction = new PayPalTransaction();
        $transaction->setParentTransactionId('123456PARENT');

        $transactionFactory = new PayPalTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config
        );

        $this->assertEquals($this->minimalCaptureTransaction(), $transactionFactory->create([]));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testVoidWithWrongCommandSubject()
    {
        $transaction = new PayPalTransaction();
        $transaction->setParentTransactionId('123456PARENT');

        $transactionFactory = new PayPalTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config
        );

        $this->assertEquals($this->minimalRefundTransaction(), $transactionFactory->void([]));
    }

    public function testRefund()
    {
        $transaction = new PayPalTransaction();
        $transaction->setParentTransactionId('123456PARENT');

        $transactionFactory = new PayPalTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config
        );

        $this->assertEquals($this->minimalRefundTransaction(), $transactionFactory->refund($this->commandSubject));
    }
}
