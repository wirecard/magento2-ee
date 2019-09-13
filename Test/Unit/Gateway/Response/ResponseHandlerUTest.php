<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Response;

use Magento\Checkout\Model\Session;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Gateway\Helper\Payment as PaymentHelper;
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

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Session
     */
    private $session;

    /**
     * @var array
     */
    private $paymentData;

    /**
     * @var array
     */
    private $paymentDataAuthorization;

    /**
     * @var PaymentHelper|PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentHelper;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Payment|PHPUnit_Framework_MockObject_MockObject
     */
    private $payment;

    /**
     * @var array
     */
    private $subject = [];

    public function setUp()
    {
        $this->logger = $this->getMock(LoggerInterface::class);
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods([self::SET_REDIRECT_URL, 'setFormMethod', 'setFormUrl', 'setFormFields',
                'unsRedirectUrl', 'unsFormMethod', 'unsFormUrl', 'unsFormFields'])
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
        $this->payment = $this->getMockWithoutInvokingTheOriginalConstructor(Payment::class);
        $paymentDO->method('getPayment')->willReturn($this->payment);
        $this->subject = [
            'payment' => $paymentDO
        ];

        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)->disableOriginalConstructor()->getMock();
        $this->urlBuilder->method('getRouteUrl')->willReturn('http://magen.to/');

        $this->paymentHelper = $this->getMockWithoutInvokingTheOriginalConstructor(PaymentHelper::class);
    }

    public function testHandlingReturnsRedirect()
    {
        $sessionMock = $this->session;
        $handler = new ResponseHandler($this->logger, $sessionMock, $this->urlBuilder, $this->paymentHelper);

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
        $handler = new ResponseHandler($this->logger, $sessionMock, $this->urlBuilder, $this->paymentHelper);

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
        $handler = new ResponseHandler($this->logger, $sessionMock, $this->urlBuilder, $this->paymentHelper);

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

    public function testHandlingReturnsFormCreditCard()
    {
        $sessionMock = $this->session;
        $handler = new ResponseHandler($this->logger, $sessionMock, $this->urlBuilder, $this->paymentHelper);

        $response = $this->getMockBuilder(FormInteractionResponse::class)->disableOriginalConstructor()->getMock();
        $response->method('getMethod')->willReturn('post');
        $response->method('getUrl')->willReturn('http://redirpost.ect');
        $response->method('getFormFields')->willReturn(['food' => 'burger']);
        $response->method(self::GET_DATA)->willReturn($this->paymentData);

        $this->payment->method('getMethod')->willReturn(ResponseHandler::FRONTEND_CODE_CREDITCARD);

        $this->paymentHelper->method('addTransaction')->with($this->payment, $response, false,
            PaymentHelper::POSTFIX_ORDER);
        $handler->handle($this->subject, [self::PAYMENT_SDK_PHP => $response]);
    }

    public function testHandlingReturnsSuccess()
    {
        $sessionMock = $this->session;
        $handler = new ResponseHandler($this->logger, $sessionMock, $this->urlBuilder, $this->paymentHelper);

        $response = $this->getMockBuilder(SuccessResponse::class)->disableOriginalConstructor()->getMock();
        $response->method(self::GET_DATA)->willReturn($this->paymentData);

        /** @var PHPUnit_Framework_MockObject_MockObject $sessionMock */
        $sessionMock->expects($this->once())->method(self::SET_REDIRECT_URL)->with('http://magen.to/frontend/redirect');
        $handler->handle($this->subject, [self::PAYMENT_SDK_PHP => $response]);
    }

    public function testHandlingLogsFailure()
    {
        $loggerMock = $this->logger;
        $handler = new ResponseHandler($loggerMock, $this->session, $this->urlBuilder, $this->paymentHelper);

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
        $handler = new ResponseHandler($loggerMock, $this->session, $this->urlBuilder, $this->paymentHelper);

        $loggerString ='Unexpected result object for notifications.';

        /** @var $loggerMock PHPUnit_Framework_MockObject_MockObject */
        $loggerMock->expects($this->once())->method('warning')->with($loggerString);

        $handler->handle($this->subject, [self::PAYMENT_SDK_PHP => null]);
    }
}
