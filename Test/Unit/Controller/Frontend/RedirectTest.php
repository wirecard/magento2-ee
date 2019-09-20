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
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Repository as PaymentRepository;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Controller\Frontend\Redirect as RedirectController;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;
use Zend\Stdlib\ParametersInterface;

require_once __DIR__ . '/../../../Stubs/OrderAddressExtensionInterface.php';

class RedirectTest extends PHPUnit_Framework_TestCase
{
    const GET_STATUS = 'getStatus';
    const CHECKOUT_ONEPAGE_SUCCESS = 'checkout/onepage/success';
    const ADD_NOTICE_MESSAGE = 'addNoticeMessage';
    const SET_PATH = 'setPath';
    const HANDLE_RESPONSE = 'handleResponse';

    /**
     * @var Redirect|PHPUnit_Framework_MockObject_MockObject
     */
    private $redirectResult;

    /**
     * @var ManagerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $messageManager;

    /**
     * @var TransactionService|PHPUnit_Framework_MockObject_MockObject
     */
    private $transactionService;

    /**
     * @var RedirectController
     */
    private $controller;

    /**
     * @var Http|PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var Session|PHPUnit_Framework_MockObject_MockObject
     */
    private $session;

    /**
     * @var \Wirecard\ElasticEngine\Gateway\Helper\Order|PHPUnit_Framework_MockObject_MockObject
     */
    private $orderHelper;

    /**
     * @var \Wirecard\ElasticEngine\Gateway\Helper\Payment|PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentHelper;

    /**
     * @var PaymentRepository|PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentRepository;

    /**
     * @var OrderInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $order;

    /**
     * @var Payment|PHPUnit_Framework_MockObject_MockObject
     */
    private $payment;

    /** @var LoggerInterface $logger */
    private $logger;

    /**
     * @var ResultFactory|PHPUnit_Framework_MockObject_MockObject
     */
    private $resultFactory;

    /**
     * @var UrlInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilder;

    /**
     * @var Json|PHPUnit_Framework_MockObject_MockObject
     */
    private $resultJson;

    public function setUp()
    {
        /**
         * @var $context Context|PHPUnit_Framework_MockObject_MockObject
         */
        $context              = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory  = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->redirectResult = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultJson     = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlBuilder     = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->method('getResultFactory')->willReturn($this->resultFactory);
        $context->method('getUrl')->willReturn($this->urlBuilder);

        $this->messageManager = $this->getMock(ManagerInterface::class);
        $context->method('getMessageManager')->willReturn($this->messageManager);

        $this->request = $this->getMockWithoutInvokingTheOriginalConstructor(Http::class);
        $this->request->method('getParams')->willReturn(['request_id' => '1234']);
        $this->request->method('getContent')->willReturn('<xmlContent></xmlContent>');

        $context->method('getRequest')->willReturn($this->request);

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionService = $this->getMockWithoutInvokingTheOriginalConstructor(TransactionService::class);
        /**
         * @var $transactionServiceFactory TransactionServiceFactory|PHPUnit_Framework_MockObject_MockObject
         */
        $transactionServiceFactory = $this->getMockWithoutInvokingTheOriginalConstructor(TransactionServiceFactory::class);
        $transactionServiceFactory->method('create')->willReturn($this->transactionService);

        $this->orderHelper = $this->getMockWithoutInvokingTheOriginalConstructor(\Wirecard\ElasticEngine\Gateway\Helper\Order::class);
        $this->paymentHelper = $this->getMockWithoutInvokingTheOriginalConstructor(\Wirecard\ElasticEngine\Gateway\Helper\Payment::class);

        $this->paymentRepository = $this->getMockWithoutInvokingTheOriginalConstructor(PaymentRepository::class);
        $this->logger            = $this->getMock(LoggerInterface::class);

        $this->payment = $this->getMockWithoutInvokingTheOriginalConstructor(Payment::class);
        $this->order   = $this->getMockWithoutInvokingTheOriginalConstructor(Order::class);
        $this->order->method('getPayment')->willReturn($this->payment);

        $this->session->method('getLastRealOrder')->willReturn($this->order);

        $this->controller = new RedirectController(
            $context,
            $this->session,
            $transactionServiceFactory,
            $this->orderHelper,
            $this->logger,
            $this->paymentHelper
        );
    }

    public function testExecuteWithoutParam()
    {
        $this->setIsPost(false);

        $this->urlBuilder->method('getRedirectUrl')->willReturn('checkout/cart');
        $this->resultFactory->expects($this->once())->method('create')->willReturn($this->resultJson);
        $this->session->expects($this->once())->method('restoreQuote');
        $this->resultJson->expects($this->once())->method('setData')->with(['redirect-url' => 'checkout/cart']);
        $this->controller->execute();
    }

    public function testExecuteNonThreeDSuccessResponse()
    {
        $this->setNonThreeDParams();
        $this->urlBuilder->method('getRedirectUrl')->willReturn('onepage/success');

        $successResponse = $this->getMockWithoutInvokingTheOriginalConstructor(SuccessResponse::class);
        $this->transactionService->method('processJsResponse')->willReturn($successResponse);

        $this->resultFactory->expects($this->at(1))->method('create')->willReturn($this->resultJson);
        $this->resultJson->expects($this->once())->method('setData')->with(['redirect-url' => 'onepage/success']);

        $this->controller->execute();
    }

    public function testExecuteNonThreeDFailureResponse()
    {
        $this->setNonThreeDParams();
        $this->urlBuilder->method('getRedirectUrl')->willReturn('checkout/cart');

        $failureResponse = $this->getMockWithoutInvokingTheOriginalConstructor(FailureResponse::class);
        $this->transactionService->method('processJsResponse')->willReturn($failureResponse);

        $this->resultFactory->expects($this->at(1))->method('create')->willReturn($this->resultJson);

        $this->session->expects($this->once())->method('restoreQuote');
        $this->messageManager->expects($this->once())->method(self::ADD_NOTICE_MESSAGE)->with($this->equalTo('order_error'));
        $this->resultJson->expects($this->once())->method('setData')->with(['redirect-url' => 'checkout/cart']);
        $this->controller->execute();
    }

    public function testExecuteThreeDSuccessResponse()
    {
        $this->setIsPost(true);

        $order   = $this->getMockWithoutInvokingTheOriginalConstructor(Order::class);
        $payment = $this->getMockWithoutInvokingTheOriginalConstructor(Payment::class);
        $order->method('getPayment')->willReturn($payment);
        $this->orderHelper->method('getOrderByIncrementId')->willReturn($order);

        $postParams = $this->getMock(ParametersInterface::class);
        $postParams->method('toArray')->willReturn(['merchant-account-id' => '1234']);
        $this->request->method('getParam')->willReturn('creditcard');
        $this->request->method('getPost')->willReturn($postParams);
        $this->urlBuilder->method('getRedirectUrl')->willReturn(self::CHECKOUT_ONEPAGE_SUCCESS);

        $successResponse = $this->getMockWithoutInvokingTheOriginalConstructor(SuccessResponse::class);
        $this->transactionService->method('handleResponse')->willReturn($successResponse);

        $this->resultFactory->expects($this->once())->method('create')->willReturn($this->redirectResult);
        $this->redirectResult->expects($this->once())->method(self::SET_PATH)->with($this->equalTo(self::CHECKOUT_ONEPAGE_SUCCESS), $this->isSecure());

        $this->controller->execute();
    }

    public function testExecuteThreeDSuccessResponseWithRequestId()
    {
        $this->setIsPost(true);

        $order   = $this->getMockWithoutInvokingTheOriginalConstructor(Order::class);
        $payment = $this->getMockWithoutInvokingTheOriginalConstructor(Payment::class);
        $order->method('getPayment')->willReturn($payment);
        $this->orderHelper->method('getOrderByIncrementId')->willReturn($order);

        $postParams = $this->getMock(ParametersInterface::class);
        $postParams->method('toArray')->willReturn(['merchant-account-id' => '1234']);
        $this->request->method('getParam')->willReturn('creditcard');
        $this->request->method('getPost')->willReturn($postParams);
        $this->urlBuilder->method('getRedirectUrl')->willReturn(self::CHECKOUT_ONEPAGE_SUCCESS);

        $successResponse = $this->getMockWithoutInvokingTheOriginalConstructor(SuccessResponse::class);
        $customFields = $this->getMock(CustomFieldCollection::class);
        $customFields->method('get')->with('orderId')->willReturn('12345');
        $successResponse->method('getCustomFields')->willReturn($customFields);

        $this->transactionService->method('handleResponse')->willReturn($successResponse);

        $this->resultFactory->expects($this->once())->method('create')->willReturn($this->redirectResult);
        $this->redirectResult->expects($this->once())->method(self::SET_PATH)->with($this->equalTo(self::CHECKOUT_ONEPAGE_SUCCESS), $this->isSecure());

        $this->controller->execute();
    }

    public function testExecuteThreeDFailureResponse()
    {
        $this->setIsPost(true);
        $postParams = $this->getMock(ParametersInterface::class);
        $postParams->method('toArray')->willReturn(['merchant-account-id' => '1234']);
        $this->request->method('getParam')->willReturn('creditcard');
        $this->request->method('getPost')->willReturn($postParams);
        $this->urlBuilder->method('getRedirectUrl')->willReturn('checkout/cart');

        $failureResponse = $this->getMockWithoutInvokingTheOriginalConstructor(FailureResponse::class);
        $this->transactionService->method('handleResponse')->willReturn($failureResponse);
        $this->resultFactory->expects($this->once())->method('create')->willReturn($this->redirectResult);

        $this->session->expects($this->once())->method('restoreQuote');
        $this->messageManager->expects($this->once())->method(self::ADD_NOTICE_MESSAGE)->with($this->equalTo('order_error'));
        $this->redirectResult->expects($this->once())->method(self::SET_PATH)->with($this->equalTo('checkout/cart'), $this->isSecure());
        $this->controller->execute();
    }

    private function setNonThreeDParams()
    {
        $this->setIsPost(true);
        $postParams = $this->getMock(ParametersInterface::class);
        $postParams->method('toArray')->willReturn(['data' => ['merchant-account-id' => '1234']]);
        $this->request->method('getParam')->willReturn('creditcard');
        $this->request->method('getPost')->willReturn($postParams);
    }

    /**
     * @param $value
     */
    private function setIsPost($value)
    {
        $this->request->method('isPost')->willReturn($value);
    }

    /**
     * @return \PHPUnit_Framework_Constraint_IsEqual
     */
    private function isSecure()
    {
        return $this->equalTo(['_secure' => true]);
    }
}
