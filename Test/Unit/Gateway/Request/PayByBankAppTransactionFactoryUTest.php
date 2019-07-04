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
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Wirecard\ElasticEngine\Gateway\Request\AccountHolderFactory;
use Wirecard\ElasticEngine\Gateway\Request\BasketFactory;
use Wirecard\ElasticEngine\Gateway\Request\PayByBankAppTransactionFactory;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Device;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\PayByBankAppTransaction;

class PayByBankAppTransactionFactoryUTest extends \PHPUnit_Framework_TestCase
{
    const REDIRECT_URL = 'http://magen.to/frontend/redirect?method=zapp';
    const ORDER_ID = '1234567';
    const RETURNSTRING = 'http://exampl.com/return';

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
            [$this->equalTo('send_additional'), $this->isNull()],
            [$this->equalTo('zapp_merchant_return_string')]
            )
            ->willReturnOnConsecutiveCalls(0, self::RETURNSTRING);

        $this->order = $this->getMockBuilder(OrderAdapterInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->order->method('getOrderIncrementId')->willReturn(self::ORDER_ID);
        $this->order->method('getGrandTotalAmount')->willReturn(1.0);
        $this->order->method('getCurrencyCode')->willReturn('GBP');

        $this->payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $this->payment->method('getParentTransactionId')->willReturn('123456PARENT');
        $this->payment->method('getAdditionalInformation')->willReturn('mypersonaltoken');

        $this->paymentDo = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->paymentDo->method('getPayment')->willReturn($this->payment);
        $this->paymentDo->method('getOrder')->willReturn($this->order);

        $this->commandSubject = ['payment' => $this->paymentDo, 'amount' => 1.0];

        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()->getMock();

        $this->transaction = $this->getMockBuilder(Transaction::class)->disableOriginalConstructor()->getMock();
    }

    public function testRefundOperationSetter()
    {
        $transactionFactory = new PayByBankAppTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            new PayByBankAppTransaction(),
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config,
            $this->request
        );
        $expected = Operation::CANCEL;
        $this->assertEquals($expected, $transactionFactory->getRefundOperation());
    }

    public function testCreateMinimum()
    {
        $transaction = new PayByBankAppTransaction();
        $transactionFactory = new PayByBankAppTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config,
            $this->request
        );

        $expected = $this->minimumExpectedTransaction();

        $this->assertEquals($expected, $transactionFactory->create($this->commandSubject));
    }

    public function testCreateWithUserAgent()
    {
        $this->request->method('getServer')->willReturn('User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 6P ' .
            'Build/MDB08L) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.69 Mobile Safari/537.36');
        $transaction = new PayByBankAppTransaction();
        $transactionFactory = new PayByBankAppTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config,
            $this->request
        );

        $expected = $this->minimumExpectedTransaction();

        $device = new Device();
        $device->setType('mobile');
        $device->setOperatingSystem('android');
        $expected->setDevice($device);

        $this->assertEquals($expected, $transactionFactory->create($this->commandSubject));
    }

    public function testCreateWithMalformedUserAgent()
    {
        $this->request->method('getServer')->willReturn('foo');
        $transaction = new PayByBankAppTransaction();
        $transactionFactory = new PayByBankAppTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config,
            $this->request
        );

        $expected = $this->minimumExpectedTransaction();

        $this->assertEquals($expected, $transactionFactory->create($this->commandSubject));
    }

    public function testRefund()
    {
        $transaction = new PayByBankAppTransaction();
        $transaction->setParentTransactionId('123456PARENT');

        $transactionFactory = new PayByBankAppTransactionFactory(
            $this->urlBuilder,
            $this->resolver,
            $this->storeManager,
            $transaction,
            $this->basketFactory,
            $this->accountHolderFactory,
            $this->config,
            $this->request
        );

        $this->assertEquals($this->minimumExpectedRefundTransaction(), $transactionFactory->refund($this->commandSubject));
    }

    /**
     * @return PayByBankAppTransaction
     */
    private function minimumExpectedTransaction()
    {
        $expected = new PayByBankAppTransaction();
        $expected->setAmount(new Amount(1.0, 'GBP'));
        $expected->setNotificationUrl('http://magen.to/frontend/notify?orderId=' . self::ORDER_ID);
        $expected->setRedirect(new Redirect(
            self::REDIRECT_URL,
            'http://magen.to/frontend/cancel?method=zapp',
            self::REDIRECT_URL
        ));

        $customFields = new CustomFieldCollection();
        $customFields->add($this->makeCustomField('MerchantRtnStrng', self::RETURNSTRING));
        $customFields->add($this->makeCustomField('TxType', 'PAYMT'));
        $customFields->add($this->makeCustomField('DeliveryType', 'DELTAD'));
        $expected->setCustomFields($customFields);
        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');

        $device = new Device();
        $device->setType('other');
        $device->setOperatingSystem('other');
        $expected->setDevice($device);

        return $expected;
    }

    /**
     * @return PayByBankAppTransaction
     */
    private function minimumExpectedRefundTransaction()
    {
        $expected = new PayByBankAppTransaction();
        $expected->setParentTransactionId('123456PARENT');

        $expected->setAmount(new Amount(1.0, 'GBP'));
        $expected->setLocale('en');
        $expected->setEntryMode('ecommerce');

        $customFields = new CustomFieldCollection();
        $customFields->add($this->makeCustomField('RefundReasonType', 'LATECONFIRMATION'));
        $customFields->add($this->makeCustomField('RefundMethod', 'BACS'));
        $expected->setCustomFields($customFields);

        return $expected;
    }

    /**
     * helper
     * @param $key
     * @param $value
     * @return CustomField
     */
    protected function makeCustomField($key, $value)
    {
        $customField = new CustomField($key, $value);
        $customField->setPrefix('zapp.in.');
        return $customField;
    }
}
