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

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Response;

use Magento\Checkout\Model\Session;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Gateway\Response\ResponseHandler;
use Wirecard\PaymentSdk\Entity\Status;
use Wirecard\PaymentSdk\Entity\StatusCollection;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;

class ResponseHandlerUTest extends \PHPUnit_Framework_TestCase
{
    const PAYMENT_SDK_PHP = 'paymentSDK-php';
    const SET_REDIRECT_URL = 'setRedirectUrl';
    const GET_DATA = 'getData';

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $session;

    /**
     * @var Array
     */
    private $paymentData;

    /**
     * @var Array
     */
    private $paymentDataAuthorization;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function setUp()
    {
        $this->logger = $this->getMock(LoggerInterface::class);
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods([self::SET_REDIRECT_URL, 'setFormMethod', 'setFormUrl', 'setFormFields'])
            ->getMock();

        $this->paymentData = [
            'providerTransactionId' => 1234,
            'transaction-type' => 'authorization',
            'providerTransactionReferenceId' => 1234567,
            'requestId' => '1-2-3',
            'maskedAccountNumber' => '5151***5485',
            'authorizationCode' => '1515',
            'cardholderAuthenticationStatus' => 'Y',
            'creditCardToken' => '0123456CARDTOKEN'
        ];

        $this->paymentDataAuthorization = [
            'providerTransactionId' => 1234,
            'providerTransactionReferenceId' => 1234567,
            'requestId' => '1-2-3',
            'maskedAccountNumber' => '5151***5485',
            'authorizationCode' => '1515',
            'cardholderAuthenticationStatus' => 'Y',
            'creditCardToken' => '0123456CARDTOKEN'
        ];

        $paymentDO = $this->getMock(PaymentDataObjectInterface::class);
        $payment = $this->getMockWithoutInvokingTheOriginalConstructor(Payment::class);
        $paymentDO->method('getPayment')->willReturn($payment);
        $this->subject = [
            'payment' => $paymentDO
        ];

        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();
        $this->urlBuilder->method('getRouteUrl')->willReturn('http://magen.to/');
    }

    public function testHandlingReturnsRedirect()
    {
        $sessionMock = $this->session;
        $handler = new ResponseHandler($this->logger, $sessionMock, $this->urlBuilder);

        $response = $this->getMockBuilder(InteractionResponse::class)->disableOriginalConstructor()->getMock();
        $response->method('getRedirectUrl')->willReturn('http://redir.ect');
        $response->method(self::GET_DATA)->willReturn($this->paymentData);

        /** @var PHPUnit_Framework_MockObject_MockObject $sessionMock */
        $sessionMock->expects($this->once())->method(self::SET_REDIRECT_URL)->with('http://redir.ect');
        $handler->handle($this->subject, [self::PAYMENT_SDK_PHP => $response]);
    }

    public function testHandlingAutorization()
    {
        $sessionMock = $this->session;
        $handler = new ResponseHandler($this->logger, $sessionMock, $this->urlBuilder);

        $response = $this->getMockBuilder(InteractionResponse::class)->disableOriginalConstructor()->getMock();
        $response->method('getRedirectUrl')->willReturn('http://redir.ect');
        $response->method(self::GET_DATA)->willReturn($this->paymentDataAuthorization);

        /** @var PHPUnit_Framework_MockObject_MockObject $sessionMock */
        $sessionMock->expects($this->once())->method(self::SET_REDIRECT_URL)->with('http://redir.ect');
        $handler->handle($this->subject, [self::PAYMENT_SDK_PHP => $response]);
    }

    public function testHandlingReturnsForm()
    {
        $sessionMock = $this->session;
        $handler = new ResponseHandler($this->logger, $sessionMock, $this->urlBuilder);

        $response = $this->getMockBuilder(FormInteractionResponse::class)->disableOriginalConstructor()->getMock();
        $response->method('getMethod')->willReturn('post');
        $response->method('getUrl')->willReturn('http://redirpost.ect');
        $response->method('getFormFields')->willReturn(['food' => 'burger']);
        $response->method(self::GET_DATA)->willReturn($this->paymentData);

        /** @var PHPUnit_Framework_MockObject_MockObject $sessionMock */
        $sessionMock->expects($this->once())->method('setFormMethod')->with('post');
        $sessionMock->expects($this->once())->method('setFormUrl')->with('http://redirpost.ect');
        $sessionMock->expects($this->once())->method('setFormFields')->with([['key' => 'food', 'value' => 'burger']]);
        $handler->handle($this->subject, [self::PAYMENT_SDK_PHP => $response]);
    }

    public function testHandlingReturnsSuccess()
    {
        $sessionMock = $this->session;
        $handler = new ResponseHandler($this->logger, $sessionMock, $this->urlBuilder);

        $response = $this->getMockBuilder(SuccessResponse::class)->disableOriginalConstructor()->getMock();
        $response->method(self::GET_DATA)->willReturn($this->paymentData);

        /** @var PHPUnit_Framework_MockObject_MockObject $sessionMock */
        $sessionMock->expects($this->once())->method(self::SET_REDIRECT_URL)->with('http://magen.to/frontend/redirect');
        $handler->handle($this->subject, [self::PAYMENT_SDK_PHP => $response]);
    }

    public function testHandlingLogsFailure()
    {
        $loggerMock = $this->logger;
        $handler = new ResponseHandler($loggerMock, $this->session, $this->urlBuilder);

        $statusCollection = new StatusCollection();
        $statusCollection->add(new Status('123', 'error_description', 'severity'));

        $response = $this->getMockBuilder(FailureResponse::class)->disableOriginalConstructor()->getMock();
        $response->method('getStatusCollection')->willReturn($statusCollection);

        $loggerString ='Error occurred: error_description (123).';

        /** @var $loggerMock PHPUnit_Framework_MockObject_MockObject */
        $loggerMock->expects($this->once())->method('error')->with($loggerString);

        $handler->handle($this->subject, [self::PAYMENT_SDK_PHP => $response]);
    }

    public function testHandlingLogsOther()
    {
        $loggerMock = $this->logger;
        $handler = new ResponseHandler($loggerMock, $this->session, $this->urlBuilder);

        $loggerString ='Unexpected result object for notifications.';

        /** @var $loggerMock PHPUnit_Framework_MockObject_MockObject */
        $loggerMock->expects($this->once())->method('warning')->with($loggerString);

        $handler->handle($this->subject, [self::PAYMENT_SDK_PHP => null]);
    }
}
