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
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
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

    public function setUp()
    {
        /**
         * @var $context Context|\PHPUnit_Framework_MockObject_MockObject
         */
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->redirectResult = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultFactory->method('create')->willReturn($this->redirectResult);
        $context->method('getResultFactory')->willReturn($resultFactory);

        $this->messageManager = $this->getMock(ManagerInterface::class);
        $context->method('getMessageManager')->willReturn($this->messageManager);

        $postParams = $this->getMock(ParametersInterface::class);
        $postParams->method('toArray')->willReturn(['MD' => 'payload', 'PaRes' => 'payload']);

        $this->request = $this->getMockWithoutInvokingTheOriginalConstructor(Http::class);
        $this->request->method('getPost')->willReturn($postParams);
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
        $this->controller = new RedirectController($context, $this->session, $transactionServiceFactory);
    }

    public function testExecuteWithGetSuccess()
    {
        $this->setIsPost(false);
        $this->setIsGet(true);
        $successResponse = $this->getMockWithoutInvokingTheOriginalConstructor(SuccessResponse::class);
        $this->transactionService->method(self::HANDLE_RESPONSE)->willReturn($successResponse);

        $this->redirectResult->expects($this->once())->method(self::SET_PATH)->with($this->equalTo(self::CHECKOUT_ONEPAGE_SUCCESS), $this->isSecure());
        $this->controller->execute();
    }

    public function testExecuteWithGetFailure()
    {
        $this->setIsPost(false);
        $this->setIsGet(true);
        $failureResponse = $this->getMockWithoutInvokingTheOriginalConstructor(FailureResponse::class);
        $this->transactionService->method(self::HANDLE_RESPONSE)->willReturn($failureResponse);

        $this->session->expects($this->once())->method('restoreQuote');
        $this->messageManager->expects($this->once())->method(self::ADD_NOTICE_MESSAGE)->with($this->equalTo('An error occurred during the payment process. Please try again.'));
        $this->redirectResult->expects($this->once())->method(self::SET_PATH)->with($this->equalTo('checkout/cart'), $this->isSecure());
        $this->controller->execute();
    }

    public function testExecuteWithoutParam()
    {
        $this->setIsPost(false);
        $this->setIsGet(false);
        $this->redirectResult->expects($this->once())->method(self::SET_PATH)->with($this->equalTo('checkout/cart'), $this->isSecure());
        $this->session->expects($this->once())->method('restoreQuote');
        $this->controller->execute();
    }

    public function testExecuteSuccessResponse()
    {
        $this->setIsPost(true);
        $this->setIsGet(false);
        $successResponse = $this->getMockWithoutInvokingTheOriginalConstructor(SuccessResponse::class);
        $this->transactionService->method(self::HANDLE_RESPONSE)->willReturn($successResponse);

        $this->redirectResult->expects($this->once())->method(self::SET_PATH)->with($this->equalTo(self::CHECKOUT_ONEPAGE_SUCCESS), $this->isSecure());
        $this->controller->execute();
    }

    public function testExecuteFailureResponse()
    {
        $this->setIsPost(true);
        $this->setIsGet(false);
        $failureResponse = $this->getMockWithoutInvokingTheOriginalConstructor(FailureResponse::class);
        $this->transactionService->method(self::HANDLE_RESPONSE)->willReturn($failureResponse);

        $this->session->expects($this->once())->method('restoreQuote');
        $this->messageManager->expects($this->once())->method(self::ADD_NOTICE_MESSAGE)->with($this->equalTo('An error occurred during the payment process. Please try again.'));
        $this->redirectResult->expects($this->once())->method(self::SET_PATH)->with($this->equalTo('checkout/cart'), $this->isSecure());
        $this->controller->execute();
    }

    /**
     * @param $value
     */
    private function setIsPost($value)
    {
        $this->request->method('isPost')->willReturn($value);
    }

    /**
     * @param $value
     */
    private function setIsGet($value)
    {
        $this->request->method('isGet')->willReturn($value);
        $this->request->method('getParam')->willReturn('1234');
    }

    /**
     * @return \PHPUnit_Framework_Constraint_IsEqual
     */
    private function isSecure()
    {
        return $this->equalTo(['_secure' => true]);
    }

    public function setUpForPayPal()
    {
        /**
         * @var $context Context|\PHPUnit_Framework_MockObject_MockObject
         */
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->redirectResult = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultFactory->method('create')->willReturn($this->redirectResult);
        $context->method('getResultFactory')->willReturn($resultFactory);

        $this->messageManager = $this->getMock(ManagerInterface::class);
        $context->method('getMessageManager')->willReturn($this->messageManager);

        $postParams = $this->getMock(ParametersInterface::class);
        $postParams->method('toArray')->willReturn(['eppresponse' => 'payload']);

        $this->request = $this->getMockWithoutInvokingTheOriginalConstructor(Http::class);
        $this->request->method('getPost')->willReturn($postParams);
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
        $this->controller = new RedirectController($context, $this->session, $transactionServiceFactory);
    }

    public function testPayPalSuccess()
    {
        $this->setUpForPayPal();
        $this->setIsPost(true);
        $this->setIsGet(false);
        $successResponse = $this->getMockWithoutInvokingTheOriginalConstructor(SuccessResponse::class);
        $this->transactionService->method(self::HANDLE_RESPONSE)->willReturn($successResponse);

        $this->redirectResult->expects($this->once())->method(self::SET_PATH)->with($this->equalTo(self::CHECKOUT_ONEPAGE_SUCCESS), $this->isSecure());
        $this->controller->execute();
    }

    public function setUpForRatePay()
    {
        /**
         * @var $context Context|\PHPUnit_Framework_MockObject_MockObject
         */
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->redirectResult = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultFactory->method('create')->willReturn($this->redirectResult);
        $context->method('getResultFactory')->willReturn($resultFactory);

        $this->messageManager = $this->getMock(ManagerInterface::class);
        $context->method('getMessageManager')->willReturn($this->messageManager);

        $postParams = $this->getMock(ParametersInterface::class);
        $postParams->method('toArray')->willReturn(['base64payload' => 'payload', 'psp_name' => 'payload']);

        $this->request = $this->getMockWithoutInvokingTheOriginalConstructor(Http::class);
        $this->request->method('getPost')->willReturn($postParams);
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
        $this->controller = new RedirectController($context, $this->session, $transactionServiceFactory);
    }

    public function testRatePaySuccess()
    {
        $this->setUpForRatePay();
        $this->setIsPost(true);
        $this->setIsGet(false);
        $successResponse = $this->getMockWithoutInvokingTheOriginalConstructor(SuccessResponse::class);
        $this->transactionService->method(self::HANDLE_RESPONSE)->willReturn($successResponse);

        $this->redirectResult->expects($this->once())->method(self::SET_PATH)->with($this->equalTo(self::CHECKOUT_ONEPAGE_SUCCESS), $this->isSecure());
        $this->controller->execute();
    }
}
