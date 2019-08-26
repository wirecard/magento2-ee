<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Request;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Wirecard\ElasticEngine\Gateway\Request\AccountHolderFactory;
use Wirecard\ElasticEngine\Gateway\Request\BasketFactory;
use Wirecard\ElasticEngine\Gateway\Request\MasterpassTransactionFactory;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\MasterpassTransaction;
use Wirecard\PaymentSdk\Transaction\Operation;

class MasterpassTransactionFactoryUTest extends \PHPUnit_Framework_TestCase
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

    private $transactionRepository;

    private $searchCriteriaBuilder;

    private $filterBuilder;

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

        $this->config = $this->getMockBuilder(ConfigInterface::class)->disableOriginalConstructor()->getMock();

        $address = $this->getMockBuilder(AddressAdapterInterface::class)->disableOriginalConstructor()->getMock();
        $address->method('getFirstname')->willReturn('Jane');
        $address->method('getLastname')->willReturn('Doe');

        $this->basketFactory = $this->getMockBuilder(BasketFactory::class)->disableOriginalConstructor()->getMock();
        $this->basketFactory->method('create')->willReturn(new Basket());

        $this->accountHolderFactory = $this->getMockBuilder(AccountHolderFactory::class)->disableOriginalConstructor()->getMock();
        $this->accountHolderFactory->method('create')->willReturn(new AccountHolder());

        $this->order = $this->getMockBuilder(OrderAdapterInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->order->method('getOrderIncrementId')->willReturn(self::ORDER_ID);
        $this->order->method('getBillingAddress')->willReturn($address);
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

        $this->transactionRepository = $this->getMockBuilder(Repository::class)->disableOriginalConstructor()->getMock();
        $this->transactionRepository->method('getList')->willReturn([
            'items' => [
                [
                    'transaction-id' => '11111',
                    'payment-methods.0.name' => 'creditcard',
                ],

                [
                    'transaction-id' => '99999',
                    'payment-methods.0.name' => 'masterpass'
                ]
            ]
        ]);

        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->setMockClassName(SearchCriteriaBuilder::class)
            ->getMock();

        $this->searchCriteriaBuilder->method('addFilters')->willReturn($this->searchCriteriaBuilder);
        $this->searchCriteriaBuilder->method('create')->willReturn($this->searchCriteriaBuilder);

        $this->filterBuilder = $this->getMockBuilder(FilterBuilder::class)->disableOriginalConstructor()->getMock();
        $this->filterBuilder->method('setField')->willReturn($this->filterBuilder);
        $this->filterBuilder->method('setValue')->willReturn($this->filterBuilder);
        $this->filterBuilder->method('create')->willReturn($this->filterBuilder);
    }

    public function testCreateMinimum()
    {
        $transaction = new MasterpassTransaction();
        $transactionFactory = new MasterpassTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config,
            $this->transactionRepository,
            $this->searchCriteriaBuilder,
            $this->filterBuilder
        );

        $expected = $this->minimumExpectedTransaction();

        $this->assertEquals($expected, $transactionFactory->create($this->commandSubject));
    }

    public function testCaptureMinimum()
    {
        $transaction = new MasterpassTransaction();
        $transactionFactory = new MasterpassTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config,
            $this->transactionRepository,
            $this->searchCriteriaBuilder,
            $this->filterBuilder
        );

        $expected = $this->minimumExpectedCaptureTransaction();

        $this->assertEquals($expected, $transactionFactory->capture($this->commandSubject));
    }

    public function testRefundMinimum()
    {
        $transaction = new MasterpassTransaction();
        $transactionFactory = new MasterpassTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config,
            $this->transactionRepository,
            $this->searchCriteriaBuilder,
            $this->filterBuilder
        );

        $expected = $this->minimumExpectedRefundTransaction();

        $this->assertEquals($expected, $transactionFactory->refund($this->commandSubject));
    }

    public function testRefundOperationSetter()
    {
        $transaction = new MasterpassTransaction();
        $transactionFactory = new MasterpassTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config,
            $this->transactionRepository,
            $this->searchCriteriaBuilder,
            $this->filterBuilder
        );
        $expected = Operation::CANCEL;
        $this->assertEquals($expected, $transactionFactory->getRefundOperation());
    }

    public function testVoidOperationMinimum()
    {
        $transaction = new MasterpassTransaction();
        $transactionFactory = new MasterpassTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config,
            $this->transactionRepository,
            $this->searchCriteriaBuilder,
            $this->filterBuilder
        );

        $expected = $this->minimumExpectedRefundTransaction();

        $this->assertEquals($expected, $transactionFactory->void($this->commandSubject));
    }

    /**
     * @return MasterpassTransaction
     */
    private function minimumExpectedTransaction()
    {
        $expected = new MasterpassTransaction();

        $expected->setAmount(new Amount(1.0, 'EUR'));
        $expected->setNotificationUrl('http://magen.to/frontend/notify?orderId=' . self::ORDER_ID);
        $expected->setRedirect(new Redirect(
            'http://magen.to/frontend/redirect',
            'http://magen.to/frontend/cancel',
            'http://magen.to/frontend/redirect'
        ));

        $customFields = new CustomFieldCollection();
        $customFields->add(new CustomField('orderId', self::ORDER_ID));
        $expected->setCustomFields($customFields);

        $accountHolder = new AccountHolder();
        $accountHolder->setFirstName('Jane');
        $accountHolder->setLastName('Doe');
        $expected->setAccountHolder($accountHolder);

        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');
        $expected->setOrderNumber(self::ORDER_ID);

        return $expected;
    }

    /**
     * @return MasterpassTransaction
     */
    private function minimumExpectedCaptureTransaction()
    {
        $expected = new MasterpassTransaction();
        $expected->setNotificationUrl('http://magen.to/frontend/notify');
        $expected->setParentTransactionId('123456PARENT');
        $expected->setAmount(new Amount(1.0, 'EUR'));

        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');

        return $expected;
    }

    /**
     * @return MasterpassTransaction
     */
    private function minimumExpectedRefundTransaction()
    {
        $expected = new MasterpassTransaction();
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
        $transaction = new MasterpassTransaction();
        $transaction->setParentTransactionId('123456PARENT');

        $transactionFactory = new MasterpassTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config,
            $this->transactionRepository,
            $this->searchCriteriaBuilder,
            $this->filterBuilder
        );

        $this->assertEquals($this->minimumExpectedRefundTransaction(), $transactionFactory->void([]));
    }

    public function testCorrectDeterminationOfCaptureTransaction()
    {
        $transaction = new MasterpassTransaction();

        $transactionFactory = new MasterpassTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config,
            $this->transactionRepository,
            $this->searchCriteriaBuilder,
            $this->filterBuilder
        );

        $captureTransaction = $transactionFactory->capture($this->commandSubject);
        $parentTransactionId = $captureTransaction->getParentTransactionId();

        $this->assertEquals('11111', $parentTransactionId);
    }
}
