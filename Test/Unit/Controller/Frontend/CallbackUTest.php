<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Controller\Frontend;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Translate\InlineInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Controller\Frontend\Callback;
use Wirecard\ElasticEngine\Gateway\Helper\Payment;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\PaymentSdk\TransactionService;
use Zend\Stdlib\ParametersInterface;

class CallbackUTest extends \PHPUnit_Framework_TestCase
{
    const HAS_REDIRECT_URL = 'hasRedirectUrl';
    const HAS_FORM_URL = 'hasFormUrl';
    const RESPONSE_JSON = 'representJson';

    private $resultFactory;

    /**
     * @var Context|PHPUnit_Framework_MockObject_MockObject
     */
    private $context;

    /**
     * @var Json
     */
    private $json;

    private $session;

    private $response;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var $transactionServiceFactory TransactionServiceFactory|PHPUnit_Framework_MockObject_MockObject */
    private $transactionServiceFactory;

    /** @var UrlInterface $urlBuilder */
    private $urlBuilder;

    /**
     * @var Payment|PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentHelper;

    /**
     * @var OrderInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $order;

    /**
     * @var Order\Payment|PHPUnit_Framework_MockObject_MockObject
     */
    private $payment;

    /**
     * @var HttpRequest|PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    public function setUp()
    {
        $this->markTestSkipped('ObjectManager Unit Helper needs newer PHPUnit');

        /** @var InlineInterface|PHPUnit_Framework_MockObject_MockObject $inline */
        $inline = $this->getMockForAbstractClass(InlineInterface::class);
        $inline->method('processResponseBody')->willReturn(null);

        $this->json = new Json($inline);

        $this->paymentHelper = $this->getMOckBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->resultFactory->method('create')
            ->willReturn($this->json);

        $urlBuilder = $this->getMock(UrlInterface::class);
        $urlBuilder->method('getRouteUrl')
            ->willReturn('http://magen.to/');
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResultFactory', 'getUrl', 'getRequest'])
            ->getMock();
        $this->context->method('getUrl')
            ->willReturn($urlBuilder);

        $this->context->method('getResultFactory')
            ->willReturn($this->resultFactory);

        $this->payment = $this->getMockWithoutInvokingTheOriginalConstructor(Order\Payment::class);
        $this->order   = $this->getMockWithoutInvokingTheOriginalConstructor(Order::class);
        $this->order->method('getPayment')
            ->willReturn($this->payment);

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getRedirectUrl',
                self::HAS_REDIRECT_URL,
                'unsRedirectUrl',
                'getLastRealOrder',
                'getFormUrl',
                self::HAS_FORM_URL,
                'unsFormUrl',
                'getFormMethod',
                'unsFormMethod',
                'getFormFields',
                'unsFormFields'
            ])
            ->getMock();
        $this->session->method('getRedirectUrl')
            ->willReturn('http://redir.ect');
        $this->session->method('getFormUrl')
            ->willReturn('http://formpost.ect');
        $this->session->method('getFormMethod')
            ->willReturn('post');
        $this->session->method('getFormFields')
            ->willReturn('myfieldsarray');
        $this->session->method('getLastRealOrder')
            ->willReturn($this->order);

        $this->logger = $this->getMock(LoggerInterface::class);

        $transactionService              = $this->getMockWithoutInvokingTheOriginalConstructor(
            TransactionService::class
        );
        $this->transactionServiceFactory = $this->getMockWithoutInvokingTheOriginalConstructor(
            TransactionServiceFactory::class
        );
        $this->transactionServiceFactory->method('create')
            ->willReturn($transactionService);
        $this->urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);

        $postParams = $this->getMock(ParametersInterface::class);
        $postParams->method('toArray')->willReturn(['jsresponse' => 'payload']);

        $this->request = $this->getMockWithoutInvokingTheOriginalConstructor(HttpRequest::class);
        $this->request->method('getPost')
            ->willReturn($postParams);
        $this->request->method('getParams')
            ->willReturn(['request_id' => '1234']);
        $this->request->method('getContent')
            ->willReturn('<xmlContent></xmlContent>');

        $this->context->method('getRequest')
            ->willReturn($this->request);

        $this->response = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods([self::RESPONSE_JSON])
            ->getMock();
    }

    public function testGetRedirectUnsetsRedirect()
    {
        $this->request->method('getParam')
            ->willReturn(null);

        /** @var PHPUnit_Framework_MockObject_MockObject $sessionMock */
        $sessionMock = $this->session;
        $sessionMock->method(self::HAS_REDIRECT_URL)
            ->willReturn(true);
        $sessionMock->expects($this->once())
            ->method('unsRedirectUrl');

        /** @var $sessionMock Session */
        $redirect = new Callback(
            $this->context,
            $sessionMock,
            $this->logger,
            $this->transactionServiceFactory,
            $this->paymentHelper
        );
        $redirect->execute();
    }

    public function testGetFormUnsetsForm()
    {
        /** @var PHPUnit_Framework_MockObject_MockObject $sessionMock */
        $sessionMock = $this->session;
        $sessionMock->method(self::HAS_FORM_URL)
            ->willReturn(true);
        $sessionMock->expects($this->once())
            ->method('unsFormUrl');
        $sessionMock->expects($this->once())
            ->method('unsFormMethod');
        $sessionMock->expects($this->once())
            ->method('unsFormFields');

        /** @var $sessionMock Session */
        $redirect = new Callback(
            $this->context,
            $sessionMock,
            $this->logger,
            $this->transactionServiceFactory,
            $this->paymentHelper
        );
        $redirect->execute();
    }

    public function testGetFormWhenSet()
    {
        /** @var PHPUnit_Framework_MockObject_MockObject $sessionMock */
        $sessionMock = $this->session;
        $sessionMock->method(self::HAS_FORM_URL)
            ->willReturn(true);

        /** @var Session $sessionMock */
        $redirect = new Callback(
            $this->context,
            $sessionMock,
            $this->logger,
            $this->transactionServiceFactory,
            $this->paymentHelper
        );
        $result   = $redirect->execute();

        /** @var PHPUnit_Framework_MockObject_MockObject $responseMock */
        $responseMock = $this->response;
        $responseMock->expects($this->once())
            ->method(self::RESPONSE_JSON)
            ->with(json_encode([
                'status' => 'OK',
                'data' => [
                    'redirect-url' => null,
                    'form-url' => 'http://formpost.ect',
                    'form-method' => 'post',
                    'form-fields' => 'myfieldsarray'
                ]
            ]));

        /** @var $responseMock ResponseInterface */
        $result->renderResult($responseMock);
    }

    public function testGetRedirectWhenSet()
    {
        /** @var PHPUnit_Framework_MockObject_MockObject $sessionMock */
        $sessionMock = $this->session;
        $sessionMock->method(self::HAS_REDIRECT_URL)->willReturn(true);

        /** @var Session $sessionMock */
        $redirect = new Callback(
            $this->context,
            $sessionMock,
            $this->logger,
            $this->transactionServiceFactory,
            $this->paymentHelper
        );
        $result   = $redirect->execute();

        /** @var PHPUnit_Framework_MockObject_MockObject $responseMock */
        $responseMock = $this->response;
        $responseMock->expects($this->once())
            ->method(self::RESPONSE_JSON)
            ->with(json_encode([
                'status' => 'OK',
                'data' => [
                    'redirect-url' => 'http://redir.ect',
                    'form-url' => null,
                    'form-method' => null,
                    'form-fields' => null
                ]
            ]));

        /** @var $responseMock ResponseInterface */
        $result->renderResult($responseMock);
    }

    public function testGetRedirectWhenNotSet()
    {
        /** @var PHPUnit_Framework_MockObject_MockObject $sessionMock */
        $sessionMock = $this->session;
        $sessionMock->method(self::HAS_REDIRECT_URL)
            ->willReturn(false);

        /** @var Session $sessionMock */
        $redirect = new Callback(
            $this->context,
            $sessionMock,
            $this->logger,
            $this->transactionServiceFactory,
            $this->paymentHelper
        );
        $result   = $redirect->execute();

        /** @var PHPUnit_Framework_MockObject_MockObject $responseMock */
        $responseMock = $this->response;
        $responseMock->expects($this->once())
            ->method(self::RESPONSE_JSON)
            ->with(json_encode([
                'status' => 'OK',
                'data' => [
                    'redirect-url' => 'http://magen.to/frontend/redirect',
                    'form-url' => null,
                    'form-method' => null,
                    'form-fields' => null
                ]
            ]));

        /** @var $responseMock ResponseInterface */
        $result->renderResult($responseMock);
    }
}
