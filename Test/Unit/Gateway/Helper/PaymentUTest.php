<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Model;

use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use PHPUnit_Framework_MockObject_MockObject;
use Wirecard\ElasticEngine\Gateway\Helper;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;

class PaymentUTest extends \PHPUnit_Framework_TestCase
{
    public static $TRID = '23233';

    /**
     * @var Order\Payment\Repository|PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentRepository;

    /**
     * @var Order\Payment\Transaction\Repository|PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentTransactionRepository;

    /**
     * @var Order\Payment|PHPUnit_Framework_MockObject_MockObject
     */
    protected $payment;

    /**
     * @var MethodInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethod;

    /**
     * @var SuccessResponse|PHPUnit_Framework_MockObject_MockObject
     */
    protected $response;

    /**
     * @var Transaction|PHPUnit_Framework_MockObject_MockObject
     */
    protected $transacion;

    /**
     * @var Helper\Payment|PHPUnit_Framework_MockObject_MockObject
     */
    protected $helper;

    public function setup()
    {
        $this->paymentRepository = $this->getMockBuilder(Order\Payment\Repository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentTransactionRepository = $this->getMockBuilder(Order\Payment\Transaction\Repository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->payment = $this->getMockBuilder(Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentMethod = $this->getMockBuilder(MethodInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->payment->method('getMethodInstance')->willReturn($this->paymentMethod);

        $this->response = $this->getMockBuilder(SuccessResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->response->method('getTransactionId')->willReturn(self::$TRID);

        $this->transacion = $this->getMockBuilder(Transaction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->payment->method('addTransaction')->willReturn($this->transacion);

        $this->helper = new Helper\Payment($this->paymentRepository, $this->paymentTransactionRepository);
    }

    public function testAddPaymentNoSave()
    {
        $this->paymentMethod->method('getCode')->willReturn('wirecard_elasticengine_creditcard');
        $this->response->method('getData')->willReturn([]);
        $this->payment->expects($this->once())->method('setTransactionAdditionalInfo')
            ->with(Transaction::RAW_DETAILS, ['has-notify' => true]);
        $this->paymentRepository->expects($this->never())->method('save');
        $this->paymentTransactionRepository->expects($this->never())->method('save');
        $t = $this->helper->addTransaction($this->payment, $this->response, false);
        $this->assertSame($this->transacion, $t);
    }

    public function testAddPaymentWithPostfix()
    {
        $this->paymentMethod->method('getCode')->willReturn('wirecard_elasticengine_creditcard');
        $this->response->method('getData')->willReturn([]);
        $this->payment->expects($this->once())->method('setTransactionAdditionalInfo')
            ->with(Transaction::RAW_DETAILS, ['has-notify' => true]);
        $this->payment->method('setTransactionId')
            ->with(self::$TRID . '-order');
        $this->paymentRepository->expects($this->never())->method('save');
        $this->paymentTransactionRepository->expects($this->never())->method('save');
        $t = $this->helper->addTransaction($this->payment, $this->response, false, '-order');
        $this->assertSame($this->transacion, $t);
    }

    public function testAddPaymentWithFailureResponse()
    {
        $response = $this->getMockBuilder(FailureResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->payment->expects($this->never())->method('setTransactionId')
            ->with(Transaction::RAW_DETAILS, ['has-notify' => true]);
        $this->assertNull($this->helper->addTransaction($this->payment, $response));
    }

    public function testAddPaymentWithSave()
    {
        $this->paymentMethod->method('getCode')->willReturn('wirecard_elasticengine_creditcard');
        $this->response->method('getData')->willReturn([]);
        $this->payment->expects($this->once())->method('setTransactionAdditionalInfo')
            ->with(Transaction::RAW_DETAILS, ['has-notify' => true]);
        $this->paymentRepository->expects($this->once())->method('save');
        $this->paymentTransactionRepository->expects($this->once())->method('save');
        $t = $this->helper->addTransaction($this->payment, $this->response, true);
        $this->assertSame($this->transacion, $t);
    }

    public function testAddPaymentWithMergeAdditionalInformation()
    {
        $this->paymentMethod->method('getCode')->willReturn('wirecard_elasticengine_creditcard');
        $this->response->method('getData')->willReturn([]);
        $this->payment->method('getAdditionalInformation')->willReturn(['is_active_payment_token_enabler' => true]);
        $this->payment->expects($this->once())->method('setTransactionAdditionalInfo')
            ->with(Transaction::RAW_DETAILS, [
                'has-notify'                      => true,
                'is_active_payment_token_enabler' => true
            ]);

        $t = $this->helper->addTransaction($this->payment, $this->response);
        $this->assertSame($this->transacion, $t);
    }
}
