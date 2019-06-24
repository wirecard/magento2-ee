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

namespace Wirecard\ElasticEngine\Test\Unit\Controller\Frontend;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Controller\Frontend\Redirect as RedirectController;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;
use Zend\Stdlib\ParametersInterface;

require_once __DIR__ . '/../../../Stubs/OrderAddressExtensionInterface.php';

class RedirectTest extends \PHPUnit_Framework_TestCase
{
    const GET_STATUS = 'getStatus';
    const CHECKOUT_ONEPAGE_SUCCESS = 'checkout/onepage/success';
    const ADD_NOTICE_MESSAGE = 'addNoticeMessage';
    const SET_PATH = 'setPath';
    const HANDLE_RESPONSE = 'handleResponse';

    /**
     * @var Redirect|\PHPUnit_Framework_MockObject_MockObject
     */
    private $redirectResult;

    /**
     * @var ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageManager;

    /**
     * @var TransactionService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transactionService;

    /**
     * @var RedirectController
     */
    private $controller;

    /**
     * @var Http|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $session;

    /** @var LoggerInterface $logger */
    private $logger;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Json
     */
    private $resultJson;

    public function setUp()
    {
        /**
         * @var $context Context|\PHPUnit_Framework_MockObject_MockObject
         */
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->redirectResult = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->method('getResultFactory')->willReturn($this->resultFactory);
        $context->method('getUrl')->willReturn($this->urlBuilder);

        $this->resultFactory->expects($this->at(0))->method('create')->willReturn($this->redirectResult);

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
         * @var $transactionServiceFactory TransactionServiceFactory|\PHPUnit_Framework_MockObject_MockObject
         */
        $transactionServiceFactory = $this->getMockWithoutInvokingTheOriginalConstructor(TransactionServiceFactory::class);
        $transactionServiceFactory->method('create')->willReturn($this->transactionService);
        $this->logger = $this->getMock(LoggerInterface::class);

        $this->controller = new RedirectController($context, $this->session, $transactionServiceFactory, $this->logger);
    }

    public function testExecuteWithoutParam()
    {
        $this->setIsPost(false);

        $this->urlBuilder->method('getRedirectUrl')->willReturn('checkout/cart');
        $this->resultFactory->expects($this->at(1))->method('create')->willReturn($this->resultJson);

        $this->resultJson->expects($this->once())->method('setData')->with(['redirect-url' => 'checkout/cart']);
        $this->session->expects($this->once())->method('restoreQuote');
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
        $postParams = $this->getMock(ParametersInterface::class);
        $postParams->method('toArray')->willReturn(['merchant-account-id' => '1234']);
        $this->request->method('getParam')->willReturn('creditcard');
        $this->request->method('getPost')->willReturn($postParams);
        $this->urlBuilder->method('getRedirectUrl')->willReturn(self::CHECKOUT_ONEPAGE_SUCCESS);

        $successResponse = $this->getMockWithoutInvokingTheOriginalConstructor(SuccessResponse::class);
        $this->transactionService->method('handleResponse')->willReturn($successResponse);
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
