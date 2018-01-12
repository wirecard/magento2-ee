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

use Magento\Checkout\Model\Session;
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
use Wirecard\ElasticEngine\Gateway\Request\RatepayInvoiceTransactionFactory;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Device;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;

class RatepayInvoiceTransactionFactoryUTest extends \PHPUnit_Framework_TestCase
{
    const REDIRECT_URL = 'http://magen.to/frontend/redirect';
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

    private $session;

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
        $this->basketFactory->method('capture')->willReturn(new Basket());
        $this->basketFactory->method('refund')->willReturn(new Basket());

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
        $this->order->method('getGrandTotalAmount')->willReturn('1.0');
        $this->order->method('getCurrencyCode')->willReturn('EUR');

        $additionalInfo = [
            'customerDob' => '1973-12-07'
        ];
        $this->payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $this->payment->method('getParentTransactionId')->willReturn('123456PARENT');
        $this->payment->method('getAdditionalInformation')->willReturn($additionalInfo);
        $this->paymentDo = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->paymentDo->method('getOrder')->willReturn($this->order);
        $this->paymentDo->method('getPayment')->willReturn($this->payment);

        $this->commandSubject = ['payment' => $this->paymentDo, 'amount' => '1.0'];

        $this->transaction = $this->getMockBuilder(Transaction::class)->disableOriginalConstructor()->getMock();
        $this->session = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
    }

    public function testRefundOperationSetter()
    {
        $transactionFactory = new RatepayInvoiceTransactionFactory($this->urlBuilder, $this->resolver, $this->storeManager,
            new RatepayInvoiceTransaction(), $this->basketFactory, $this->accountHolderFactory, $this->config, $this->session);
        $expected = Operation::CANCEL;
        $this->assertEquals($expected, $transactionFactory->getRefundOperation());
    }

    public function testCreateMinimum()
    {
        $transaction = new RatepayInvoiceTransaction();
        $transactionFactory = new RatepayInvoiceTransactionFactory($this->urlBuilder, $this->resolver, $this->storeManager,
            $transaction, $this->basketFactory, $this->accountHolderFactory, $this->config, $this->session);

        $expected = $this->minimumExpectedTransaction();

        $this->assertEquals($expected, $transactionFactory->create($this->commandSubject));
    }

    public function testCreateWithDevice()
    {
        $transaction = new RatepayInvoiceTransaction();
        $transactionFactory = new RatepayInvoiceTransactionFactory($this->urlBuilder, $this->resolver, $this->storeManager,
            $transaction, $this->basketFactory, $this->accountHolderFactory, $this->config, $this->session);

        $expected = $this->minimumExpectedTransaction();

        $this->session->method('getData')->willReturn('12345');
        $device = new Device();
        $device->setFingerprint('12345');
        $expected->setDevice($device);

        $this->assertEquals($expected, $transactionFactory->create($this->commandSubject));
    }

    public function testCaptureMinimum()
    {
        $transaction = new RatepayInvoiceTransaction();
        $transaction->setRedirect(new Redirect(
            self::REDIRECT_URL,
            'http://magen.to/frontend/cancel',
            self::REDIRECT_URL));
        $transactionFactory = new RatepayInvoiceTransactionFactory($this->urlBuilder, $this->resolver, $this->storeManager,
            $transaction, $this->basketFactory, $this->accountHolderFactory, $this->config, $this->session);

        $expected = $this->minimumExpectedCaptureTransaction();

        $this->assertEquals($expected, $transactionFactory->capture($this->commandSubject));
    }

    public function testRefundMinimum()
    {
        $transaction = new RatepayInvoiceTransaction();
        $transaction->setParentTransactionId('123456PARENT');
        $transaction->setRedirect(new Redirect(
            self::REDIRECT_URL,
            'http://magen.to/frontend/cancel',
            self::REDIRECT_URL));
        $transactionFactory = new RatepayInvoiceTransactionFactory($this->urlBuilder, $this->resolver, $this->storeManager,
            $transaction, $this->basketFactory, $this->accountHolderFactory, $this->config, $this->session);

        $expected = $this->minimumExpectedRefundTransaction();

        $this->assertEquals($expected, $transactionFactory->refund($this->commandSubject));
    }

    public function testVoidOperationMinimum()
    {
        $transaction = new RatepayInvoiceTransaction();
        $transaction->setParentTransactionId('123456PARENT');
        $transaction->setRedirect(new Redirect(
            self::REDIRECT_URL,
            'http://magen.to/frontend/cancel',
            self::REDIRECT_URL));
        $transactionFactory = new RatepayInvoiceTransactionFactory($this->urlBuilder, $this->resolver, $this->storeManager,
            $transaction, $this->basketFactory, $this->accountHolderFactory, $this->config, $this->session);

        $expected = $this->minimumExpectedVoidTransaction();

        $this->assertEquals($expected, $transactionFactory->void($this->commandSubject));
    }

    /**
     * @return RatepayInvoiceTransaction
     */
    private function minimumExpectedTransaction()
    {
        $expected = new RatepayInvoiceTransaction();

        $expected->setAmount(new Amount(1.0, 'EUR'));
        $expected->setNotificationUrl('http://magen.to/frontend/notify');
        $expected->setRedirect(new Redirect(
            'http://magen.to/frontend/redirect',
            'http://magen.to/frontend/cancel',
            'http://magen.to/frontend/redirect'));

        $customFields = new CustomFieldCollection();
        $customFields->add(new CustomField('orderId', self::ORDER_ID));
        $expected->setCustomFields($customFields);
        $expected->setAccountHolder(new AccountHolder());
        $expected->setBasket(new Basket());
        $expected->setOrderNumber(self::ORDER_ID);
        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');

        return $expected;
    }

    /**
     * @return RatepayInvoiceTransaction
     */
    private function minimumExpectedCaptureTransaction()
    {
        $expected = new RatepayInvoiceTransaction();
        $expected->setNotificationUrl('http://magen.to/frontend/notify');
        $expected->setRedirect(new Redirect(
            self::REDIRECT_URL,
            'http://magen.to/frontend/cancel',
            self::REDIRECT_URL));
        $expected->setParentTransactionId('123456PARENT');

        $expected->setAmount(new Amount(1.0, 'EUR'));
        $expected->setBasket(new Basket());
        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');

        return $expected;
    }

    /**
     * @return RatepayInvoiceTransaction
     */
    private function minimumExpectedRefundTransaction()
    {
        $expected = new RatepayInvoiceTransaction();
        $expected->setRedirect(new Redirect(
            self::REDIRECT_URL,
            'http://magen.to/frontend/cancel',
            self::REDIRECT_URL));
        $expected->setParentTransactionId('123456PARENT');

        $expected->setAmount(new Amount(1.0, 'EUR'));
        $expected->setBasket(new Basket());
        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');

        return $expected;
    }

    /**
     * @return RatepayInvoiceTransaction
     */
    private function minimumExpectedVoidTransaction()
    {
        $expected = new RatepayInvoiceTransaction();
        $expected->setRedirect(new Redirect(
            self::REDIRECT_URL,
            'http://magen.to/frontend/cancel',
            self::REDIRECT_URL));
        $expected->setParentTransactionId('123456PARENT');

        $expected->setAmount(new Amount(1.0, 'EUR'));
        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');

        return $expected;
    }
}
