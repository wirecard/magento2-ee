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

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Wirecard\ElasticEngine\Gateway\Request\AccountHolderFactory;
use Wirecard\ElasticEngine\Gateway\Request\SepaTransactionFactory;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Mandate;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\SepaTransaction;

class SepaTransactionFactoryUTest extends \PHPUnit_Framework_TestCase
{
    const REDIRECT_URL = 'http://magen.to/frontend/redirect';
    const ORDER_ID = '1234567';

    private $urlBuilder;

    private $resolver;

    private $storeManager;

    private $config;

    private $payment;

    private $order;

    private $paymentInfo;

    private $commandSubject;

    private $repository;

    private $searchCriteriaBuilder;

    private $filterBuilder;

    private $transaction;

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

        $this->paymentInfo = $this->getMock(InfoInterface::class);

        $this->payment = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->payment->method('getOrder')->willReturn($this->order);

        $this->commandSubject = ['payment' => $this->payment, 'amount' => '1.0'];

        $filter = $this->getMockBuilder(Filter::class)->disableOriginalConstructor()->getMock();
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)->disableOriginalConstructor()->getMock();
        $transactionList = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()->getMock();
        $transactionList->method('getAllIds')->willReturn([1, 2]);

        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->method('addFilter')->willReturn($this->searchCriteriaBuilder);
        $this->searchCriteriaBuilder->method('create')->willReturn($searchCriteria);

        $this->repository = $this->getMockBuilder(Repository::class)->disableOriginalConstructor()->getMock();
        $this->repository->method('getList')->willReturn($transactionList);

        $this->transaction = $this->getMockBuilder(Transaction::class)->disableOriginalConstructor()->getMock();
        $transactionList->method('getItemById')->willReturn($this->transaction);

        $this->filterBuilder = $this->getMockBuilder(FilterBuilder::class)->disableOriginalConstructor()->getMock();
        $this->filterBuilder->method('setField')->willReturn($this->filterBuilder);
        $this->filterBuilder->method('setValue')->willReturn($this->filterBuilder);
        $this->filterBuilder->method('create')->willReturn($filter);
    }

    public function testCreateMinimum()
    {
        $transaction = new SepaTransaction();
        $transactionFactory = new SepaTransactionFactory($this->urlBuilder, $this->resolver, $this->storeManager,
            $transaction, $this->config, $this->repository, $this->searchCriteriaBuilder, $this->filterBuilder, $this->accountHolderFactory);

        $expected = $this->minimumExpectedTransaction();

        $this->assertEquals($expected, $transactionFactory->create($this->commandSubject));
    }

    public function testCaptureMinimum()
    {
        $transaction = new SepaTransaction();
        $transactionFactory = new SepaTransactionFactory($this->urlBuilder, $this->resolver, $this->storeManager,
            $transaction, $this->config, $this->repository, $this->searchCriteriaBuilder, $this->filterBuilder, $this->accountHolderFactory);

        $expected = $this->minimumExpectedCaptureTransaction();

        $this->assertEquals($expected, $transactionFactory->capture($this->commandSubject));
    }

    public function testRefundMinimum()
    {
        $transaction = new SepaTransaction();
        $transactionFactory = new SepaTransactionFactory($this->urlBuilder, $this->resolver, $this->storeManager,
            $transaction, $this->config, $this->repository, $this->searchCriteriaBuilder, $this->filterBuilder, $this->accountHolderFactory);

        $expected = $this->minimumExpectedRefundTransaction();

        $this->assertEquals($expected, $transactionFactory->refund($this->commandSubject));
    }

    public function testCreateSetsBic()
    {
        $this->config->expects($this->at(0))->method('getValue')->willReturn(true);
        $transaction = new SepaTransaction();
        $transactionFactory = new SepaTransactionFactory($this->urlBuilder, $this->resolver, $this->storeManager,
            $transaction, $this->config, $this->repository, $this->searchCriteriaBuilder, $this->filterBuilder, $this->accountHolderFactory);

        $expected = $this->minimumExpectedTransaction();
        $expected->setBic('WIREDEMMXXX');

        $this->assertEquals($expected, $transactionFactory->create($this->commandSubject));
    }

    /**
     * @return SepaTransaction
     */
    private function minimumExpectedTransaction()
    {
        $additionalInfo = [
            'bankBic' => 'WIREDEMMXXX',
            'bankAccountIban' => 'DE42512308000000060004',
            'accountFirstName' => 'Jane',
            'accountLastName' => 'Doe',
            'mandateId' => '1234'
        ];
        $this->payment->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->paymentInfo);

        $this->paymentInfo->expects(static::once())
            ->method('getAdditionalInformation')
            ->willReturn($additionalInfo);

        $expected = new SepaTransaction();

        $expected->setAmount(new Amount(1.0, 'EUR'));
        $expected->setNotificationUrl('http://magen.to/frontend/notify');
        $expected->setRedirect(new Redirect(
            'http://magen.to/frontend/redirect',
            'http://magen.to/frontend/cancel',
            'http://magen.to/frontend/redirect'));
        $customFields = new CustomFieldCollection();
        $customFields->add(new CustomField('orderId', self::ORDER_ID));
        $expected->setCustomFields($customFields);
        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');

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
     * @return SepaTransaction
     */
    private function minimumExpectedCaptureTransaction()
    {
        $expected = new SepaTransaction();
        $expected->setNotificationUrl('http://magen.to/frontend/notify');

        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');

        return $expected;
    }

    /**
     * @return SepaTransaction
     */
    private function minimumExpectedRefundTransaction()
    {
        $expected = new SepaTransaction();
        $expected->setNotificationUrl('http://magen.to/frontend/notify');

        $expected->setAccountHolder(new AccountHolder());
        $expected->setAmount(new Amount(1.0, 'EUR'));
        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');

        return $expected;
    }
}
