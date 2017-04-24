<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard Central Eastern Europe GmbH
 * (abbreviated to Wirecard CEE) and are explicitly not part of the Wirecard CEE range of
 * products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard CEE does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard CEE does not guarantee their full
 * functionality neither does Wirecard CEE assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard CEE does not guarantee the full functionality
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

use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Wirecard\ElasticEngine\Gateway\Request\TransactionFactory;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;

class TransactionFactoryUTest extends \PHPUnit_Framework_TestCase
{
    private $urlBuilder;

    private $payment;

    private $order;

    private $commandSubject;

    public function setUp()
    {
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();
        $this->urlBuilder->method('getRouteUrl')->willReturn('http://magen.to/');

        $this->order = $this->getMockBuilder(OrderAdapterInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->order->method('getGrandTotalAmount')->willReturn('1.0');
        $this->order->method('getCurrencyCode')->willReturn('EUR');
        $this->payment = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->payment->method('getOrder')->willReturn($this->order);

        $this->commandSubject = ['payment' => $this->payment];
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateThrowsExceptionWithoutPayment()
    {
        $transactionFactory = new TransactionFactory($this->urlBuilder, new PayPalTransaction());
        $transactionFactory->create([]);
    }

    public function testCreateSetsAmountValues()
    {
        $transactionMock = $this->getMock(Transaction::class);
        $transactionMock->expects($this->Once())->method('setAmount')->with($this->equalTo(new Amount('1.0', 'EUR')));

        $transactionFactory = new TransactionFactory($this->urlBuilder, $transactionMock);
        $transactionFactory->create($this->commandSubject);
    }

    public function testCreateSetsRedirect()
    {
        $transactionMock = $this->getMock(Transaction::class);
        $redirect = new Redirect('http://magen.to/frontend/back', 'http://magen.to/frontend/cancel');
        $transactionMock->expects($this->Once())->method('setRedirect')->with($this->equalTo($redirect));

        $transactionFactory = new TransactionFactory($this->urlBuilder, $transactionMock);
        $transactionFactory->create($this->commandSubject);
    }

    public function testCreateSetsNotification()
    {
        $transactionMock = $this->getMock(Transaction::class);
        $transactionMock->expects($this->Once())->method('setNotificationUrl')->with($this->equalTo('http://magen.to/notify'));

        $transactionFactory = new TransactionFactory($this->urlBuilder, $transactionMock);
        $transactionFactory->create($this->commandSubject);
    }
}
