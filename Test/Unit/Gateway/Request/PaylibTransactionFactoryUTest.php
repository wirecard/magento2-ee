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

use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
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
use Wirecard\ElasticEngine\Gateway\Request\PaylibTransactionFactory;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\PaylibTransaction;

class PaylibTransactionFactoryUTest extends \PHPUnit_Framework_TestCase
{
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

    /** @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $request;

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
        $this->config->method('getValue')->withConsecutive(
            [$this->equalTo('send_additional')]
        )->willReturnOnConsecutiveCalls(0);

        $address = $this->getMockBuilder(AddressAdapterInterface::class)->disableOriginalConstructor()->getMock();
        $address->method('getEmail')->willReturn('test@example.com');
        $address->method('getFirstname')->willReturn('John');
        $address->method('getLastname')->willReturn('Doe');

        $this->order = $this->getMockBuilder(OrderAdapterInterface::class)->disableOriginalConstructor()->getMock();
        $this->order->method('getOrderIncrementId')->willReturn(self::ORDER_ID);
        $this->order->method('getBillingAddress')->willReturn($address);
        $this->order->method('getGrandTotalAmount')->willReturn('1.01');
        $this->order->method('getCurrencyCode')->willReturn('EUR');

        $this->payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $this->payment->method('getAdditionalInformation')->willReturn('mypersonaltoken');

        $this->paymentDo = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->paymentDo->method('getPayment')->willReturn($this->payment);
        $this->paymentDo->method('getOrder')->willReturn($this->order);

        $this->commandSubject = ['payment' => $this->paymentDo, 'amount' => '1.01'];

        $this->request = $this->getMockBuilder(Http::class)->disableOriginalConstructor()->getMock();

        $this->transaction = $this->getMockBuilder(Transaction::class)->disableOriginalConstructor()->getMock();
    }

    public function testCreateMinimum()
    {
        $transactionFactory = new PaylibTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            new PaylibTransaction(),
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config,
            $this->request
        );

        $expected = $this->minimumExpectedTransaction();
        $this->assertEquals($expected, $transactionFactory->create($this->commandSubject));
    }

    /**
     * @return PaylibTransaction
     */
    private function minimumExpectedTransaction()
    {
        $expected = new PaylibTransaction();

        $expected->setAmount(new Amount(1.01, 'EUR'));
        $expected->setNotificationUrl('http://magen.to/frontend/notify?orderId=' . self::ORDER_ID);
        $expected->setRedirect(new Redirect(
            'http://magen.to/frontend/redirect?method=paylib',
            'http://magen.to/frontend/cancel?method=paylib',
            'http://magen.to/frontend/redirect?method=paylib'
        ));

        $customFields = new CustomFieldCollection();
        $customFields->add(new CustomField('orderId', self::ORDER_ID));
        $expected->setCustomFields($customFields);
        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');

        return $expected;
    }
}
