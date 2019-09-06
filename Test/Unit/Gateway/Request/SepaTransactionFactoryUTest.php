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
use Wirecard\ElasticEngine\Gateway\Request\SepaTransactionFactory;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Mandate;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction;

class SepaTransactionFactoryUTest extends \PHPUnit_Framework_TestCase
{
    const REDIRECT_URL = 'http://magen.to/frontend/redirect?method=sepadirectdebit';
    const ORDER_ID = '1234567';

    private $urlBuilder;

    private $resolver;

    private $storeManager;

    private $config;

    private $payment;

    private $paymentDo;

    private $order;

    private $commandSubject;

    private $transaction;

    private $basketFactory;

    private $accountHolderFactory;

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

        $additionalInfo = [
            'bankBic' => 'WIREDEMMXXX',
            'bankAccountIban' => 'DE42512308000000060004',
            'accountFirstName' => 'Jane',
            'accountLastName' => 'Doe',
            'mandateId' => '1234'
        ];

        $this->payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $this->payment->method('getParentTransactionId')->willReturn('123456PARENT');
        $this->payment->method('getAdditionalInformation')->willReturn($additionalInfo);
        $this->paymentDo = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->paymentDo->method('getOrder')->willReturn($this->order);
        $this->paymentDo->method('getPayment')->willReturn($this->payment);

        $this->commandSubject = ['payment' => $this->paymentDo, 'amount' => 1.0];

        $this->transaction = $this->getMockBuilder(Transaction::class)->disableOriginalConstructor()->getMock();
    }

    public function testCreateMinimum()
    {
        $transaction = new SepaDirectDebitTransaction();
        $transactionFactory = new SepaTransactionFactory(
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

    public function testCaptureMinimum()
    {
        $transaction = new SepaDirectDebitTransaction();
        $transactionFactory = new SepaTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config
        );

        $expected = $this->minimumExpectedCaptureTransaction();

        $this->assertEquals($expected, $transactionFactory->capture($this->commandSubject));
    }

    public function testCreateSetsBic()
    {
        $this->config->expects($this->at(1))->method('getValue')->willReturn(true);
        $transaction = new SepaDirectDebitTransaction();
        $transactionFactory = new SepaTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config
        );

        $expected = $this->minimumExpectedTransaction();
        $expected->setBic('WIREDEMMXXX');

        $this->assertEquals($expected, $transactionFactory->create($this->commandSubject));
    }

    /**
     * @return SepaDirectDebitTransaction
     */
    private function minimumExpectedTransaction()
    {
        $expected = new SepaDirectDebitTransaction();

        $expected->setAmount(new Amount(1.0, 'EUR'));
        $expected->setRedirect(new Redirect(
            'http://magen.to/frontend/redirect?method=sepadirectdebit',
            'http://magen.to/frontend/cancel?method=sepadirectdebit',
            'http://magen.to/frontend/redirect?method=sepadirectdebit'
        ));
        $customFields = new CustomFieldCollection();
        $customFields->add(new CustomField('orderId', self::ORDER_ID));
        $expected->setCustomFields($customFields);
        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');
        $expected->setOrderNumber(self::ORDER_ID);

        $mandate = new Mandate('1234');

        $accountHolder = new AccountHolder();
        $accountHolder->setFirstName('Jane');
        $accountHolder->setLastName('Doe');
        $expected->setAccountHolder($accountHolder);
        $expected->setIban('DE42512308000000060004');
        $expected->setMandate($mandate);

        return $expected;
    }

    /**
     * @return SepaDirectDebitTransaction
     */
    private function minimumExpectedCaptureTransaction()
    {
        $expected = new SepaDirectDebitTransaction();
        $expected->setParentTransactionId('123456PARENT');
        $expected->setAmount(new Amount(1.0, 'EUR'));

        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');

        return $expected;
    }
}
