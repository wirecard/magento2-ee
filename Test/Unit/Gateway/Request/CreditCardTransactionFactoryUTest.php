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
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Wirecard\ElasticEngine\Gateway\Helper\ThreeDsHelper;
use Wirecard\ElasticEngine\Gateway\Request\AccountHolderFactory;
use Wirecard\ElasticEngine\Gateway\Request\BasketFactory;
use Wirecard\ElasticEngine\Gateway\Request\CreditCardTransactionFactory;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\AccountInfo;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\Transaction\Operation;

class CreditCardTransactionFactoryUTest extends \PHPUnit_Framework_TestCase
{
    const REDIRECT_URL = 'http://magen.to/frontend/redirect?method=creditcard';
    const ORDER_ID = '1234567';
    const VAULT_ENABLER = 'mypersonaltoken';

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

    private $threeDsHelper;

    public function setUp()
    {
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlBuilder->method('getRouteUrl')
            ->willReturn('http://magen.to/');

        $this->resolver = $this->getMockBuilder(ResolverInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resolver->method('getLocale')
            ->willReturn('en_US');

        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $store->method('getName')
            ->willReturn('My shop name');

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager->method('getStore')
            ->willReturn($store);

        $this->basketFactory = $this->getMockBuilder(BasketFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->basketFactory->method('create')
            ->willReturn(new Basket());

        $this->accountHolderFactory = $this->getMockBuilder(AccountHolderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->accountHolderFactory->method('create')
            ->willReturn(new AccountHolder());

        $this->threeDsHelper = $this->getMockBuilder(ThreeDsHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->threeDsHelper->method('getThreeDsTransaction')
            ->willReturn(new CreditCardTransaction());

        $this->config = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->order = $this->getMockBuilder(OrderAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->order->method('getOrderIncrementId')
            ->willReturn(self::ORDER_ID);
        $this->order->method('getGrandTotalAmount')
            ->willReturn(1.0);
        $this->order->method('getCurrencyCode')
            ->willReturn('EUR');

        $this->payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()->getMock();
        $this->payment->method('getParentTransactionId')
            ->willReturn('123456PARENT');
        $this->payment->method('getAdditionalInformation')
            ->willReturn('mypersonaltoken');
        $this->paymentDo = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentDo->method('getPayment')
            ->willReturn($this->payment);
        $this->paymentDo->method('getOrder')
            ->willReturn($this->order);

        $this->commandSubject = ['payment' => $this->paymentDo, 'amount' => 1.0];

        $this->transaction = $this->getMockBuilder(Transaction::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testRefundOperationSetter()
    {
        $transactionFactory = new CreditCardTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            new CreditCardTransaction(),
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config,
            $this->threeDsHelper
        );
        $expected = Operation::REFUND;
        $this->assertEquals($expected, $transactionFactory->getRefundOperation());
    }

    public function testCreateMinimum()
    {
        $this->markTestSkipped('Rewrite needed during update for 3D Secure');
        $transaction = new CreditCardTransaction();
        $transactionFactory = new CreditCardTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config,
            $this->threeDsHelper
        );

        $expected = $this->minimumExpectedTransaction();

        $this->assertEquals($expected, $transactionFactory->create($this->commandSubject));
    }

    public function testCaptureMinimum()
    {
        $transaction = new CreditCardTransaction();
        $transactionFactory = new CreditCardTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config,
            $this->threeDsHelper
        );

        $expected = $this->minimumExpectedCaptureTransaction();

        $this->assertEquals($expected, $transactionFactory->capture($this->commandSubject));
    }

    public function testRefund()
    {
        $transaction = new CreditCardTransaction();
        $transaction->setParentTransactionId('123456PARENT');

        $transactionFactory = new CreditCardTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config,
            $this->threeDsHelper
        );

        $this->assertEquals(
            $this->minimumExpectedRefundTransaction(),
            $transactionFactory->refund($this->commandSubject)
        );
    }

    public function testVoidOperationMinimum()
    {
        $transaction = new CreditCardTransaction();
        $transactionFactory = new CreditCardTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config,
            $this->threeDsHelper
        );

        $expected = $this->minimumExpectedVoidTransaction();

        $this->assertEquals($expected, $transactionFactory->void($this->commandSubject));
    }

    /**
     * @return CreditCardTransaction
     */
    private function minimumExpectedTransaction()
    {
        $accountHolder = new AccountHolder();
        $accountHolder->setAccountInfo(new AccountInfo());

        $expected = new CreditCardTransaction();
        $expected->setTokenId('mypersonaltoken');
        $expected->setTermUrl(self::REDIRECT_URL);
        $expected->setAmount(new Amount(1.0, 'EUR'));
        $expected->setThreeD(false);
        $expected->setAccountHolder($accountHolder);
        $expected->setRedirect(new Redirect(
            self::REDIRECT_URL,
            'http://magen.to/frontend/cancel?method=creditcard',
            self::REDIRECT_URL
        ));

        $customFields = new CustomFieldCollection();
        $customFields->add(new CustomField('orderId', self::ORDER_ID));
        $expected->setCustomFields($customFields);
        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');
        $expected->setOrderNumber(self::ORDER_ID);

        return $expected;
    }

    /**
     * @return CreditCardTransaction
     */
    private function minimumExpectedCaptureTransaction()
    {
        $expected = new CreditCardTransaction();
        $expected->setParentTransactionId('123456PARENT');
        $expected->setAmount(new Amount(1.0, 'EUR'));

        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');

        return $expected;
    }

    /**
     * @return CreditCardTransaction
     */
    private function minimumExpectedRefundTransaction()
    {
        $expected = new CreditCardTransaction();
        $expected->setParentTransactionId('123456PARENT');

        $expected->setAmount(new Amount(1.0, 'EUR'));
        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');

        return $expected;
    }

    /**
     * @return CreditCardTransaction
     */
    private function minimumExpectedVoidTransaction()
    {
        $expected = new CreditCardTransaction();
        $expected->setParentTransactionId('123456PARENT');

        $expected->setAmount(new Amount(1.0, 'EUR'));
        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');

        return $expected;
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testVoidWithWrongCommandSubject()
    {
        $transaction = new CreditCardTransaction();
        $transaction->setParentTransactionId('123456PARENT');

        $transactionFactory = new CreditCardTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config,
            $this->threeDsHelper
        );

        $this->assertEquals($this->minimumExpectedVoidTransaction(), $transactionFactory->void([]));
    }
}
