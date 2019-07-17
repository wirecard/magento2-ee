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
use Wirecard\ElasticEngine\Gateway\Request\SepaCreditTransactionFactory;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;

class SepaCreditTransactionFactoryUTest extends \PHPUnit_Framework_TestCase
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
        $transactionFactory = new SepaCreditTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            new SepaCreditTransferTransaction(),
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config
        );
        $expected = Operation::CREDIT;
        $this->assertEquals($expected, $transactionFactory->getRefundOperation());
    }

    public function testRefundMinimum()
    {
        $transaction = new SepaCreditTransferTransaction();
        $transactionFactory = new SepaCreditTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config
        );

        $expected = $this->minimumExpectedRefundTransaction();

        $this->assertEquals($expected, $transactionFactory->refund($this->commandSubject));
    }

    /**
     * @return SepaCreditTransferTransaction
     */
    private function minimumExpectedRefundTransaction()
    {
        $expected = new SepaCreditTransferTransaction();
        $expected->setParentTransactionId('123456PARENT');

        $expected->setAccountHolder(null);
        $expected->setAmount(new Amount(1.0, 'EUR'));
        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');

        return $expected;
    }
}
