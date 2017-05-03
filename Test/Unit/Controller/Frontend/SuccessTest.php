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

namespace Wirecard\ElasticEngine\Test\Unit\Controller\Frontend;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Wirecard\ElasticEngine\Controller\Frontend\Success;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;
use Zend\Stdlib\ParametersInterface;

require_once __DIR__ . '/../../../Stubs/OrderAddressExtensionInterface.php';

class SuccessTest extends \PHPUnit_Framework_TestCase
{
    const GET_STATUS = 'getStatus';
    const CHECKOUT_ONEPAGE_SUCCESS = 'checkout/onepage/success';
    const ADD_NOTICE_MESSAGE = 'addNoticeMessage';
    const SET_PATH = 'setPath';
    const NO_FINAL_STATE = 'Final state of transaction could not be determined.';

    /**
     * @var OrderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $order;

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
     * @var Success
     */
    private $controller;

    /**
     * @var Http|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

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
        $postParams->method('toArray')->willReturn(['test' => 'payload']);

        $this->request = $this->getMockWithoutInvokingTheOriginalConstructor(Http::class);
        $this->request->method('getPost')->willReturn($postParams);
        $this->request->method('getContent')->willReturn('<xmlContent></xmlContent>');

        $context->method('getRequest')->willReturn($this->request);

        /**
         * @var $session Session|\PHPUnit_Framework_MockObject_MockObject
         */
        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->order = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $session->method('getLastRealOrder')->willReturn($this->order);

        $this->transactionService = $this->getMockWithoutInvokingTheOriginalConstructor(TransactionService::class);
        /**
         * @var $transactionServiceFactory TransactionServiceFactory|\PHPUnit_Framework_MockObject_MockObject
         */
        $transactionServiceFactory = $this->getMockWithoutInvokingTheOriginalConstructor(TransactionServiceFactory::class);
        $transactionServiceFactory->method('create')->willReturn($this->transactionService);
        $this->controller = new Success($context, $session, $transactionServiceFactory);
    }

    public function testExecuteWithStatusPendingPayment()
    {
        $this->order->method(self::GET_STATUS)->willReturn(Order::STATE_PENDING_PAYMENT);
        $this->setIsPost(true);
        $this->redirectResult->expects($this->once())->method(self::SET_PATH)->with($this->equalTo(self::CHECKOUT_ONEPAGE_SUCCESS), $this->isSecure());
        $this->messageManager->expects($this->once())->method(self::ADD_NOTICE_MESSAGE)->with($this->equalTo(self::NO_FINAL_STATE));
        $this->controller->execute();
    }

    public function testExecuteWithStatusPendingPaymentAndSuccessResponse()
    {
        $this->setIsPost(true);
        $successResponse = $this->getMockWithoutInvokingTheOriginalConstructor(SuccessResponse::class);
        $this->transactionService->method('handleResponse')->willReturn($successResponse);
        $this->order->method(self::GET_STATUS)->willReturn(Order::STATE_PENDING_PAYMENT);
        $this->redirectResult->expects($this->once())->method(self::SET_PATH)->with($this->equalTo(self::CHECKOUT_ONEPAGE_SUCCESS), $this->isSecure());
        $this->messageManager->expects($this->never())->method(self::ADD_NOTICE_MESSAGE)->with($this->equalTo(self::NO_FINAL_STATE));
        $this->controller->execute();
    }

    public function testExecuteWithStatusPending()
    {
        $this->setIsPost(true);
        $this->order->method(self::GET_STATUS)->willReturn('pending');
        $this->redirectResult->expects($this->once())->method(self::SET_PATH)->with($this->equalTo(self::CHECKOUT_ONEPAGE_SUCCESS), $this->isSecure());
        $this->messageManager->expects($this->once())->method(self::ADD_NOTICE_MESSAGE)->with($this->equalTo(self::NO_FINAL_STATE));
        $this->controller->execute();
    }

    public function testExecuteWithStatusPendingAndGetPayload()
    {
        $this->setIsPost(false);
        $this->order->method(self::GET_STATUS)->willReturn('pending');
        $this->redirectResult->expects($this->once())->method(self::SET_PATH)->with($this->equalTo(self::CHECKOUT_ONEPAGE_SUCCESS), $this->isSecure());
        $this->messageManager->expects($this->once())->method(self::ADD_NOTICE_MESSAGE)->with($this->equalTo('Invalid request to success redirect page.'));
        $this->controller->execute();
    }

    public function testExecuteWithStatusProcessing()
    {
        $this->setIsPost(true);
        $this->order->method(self::GET_STATUS)->willReturn(Order::STATE_PROCESSING);
        $this->redirectResult->expects($this->once())->method(self::SET_PATH)->with($this->equalTo(self::CHECKOUT_ONEPAGE_SUCCESS), $this->isSecure());
        $this->messageManager->expects($this->never())->method(self::ADD_NOTICE_MESSAGE);
        $this->controller->execute();
    }

    public function testExecuteWithStatusCanceled()
    {
        $this->setIsPost(true);
        $this->order->method(self::GET_STATUS)->willReturn(Order::STATE_CANCELED);
        $this->redirectResult->expects($this->once())->method(self::SET_PATH)->with($this->equalTo('checkout/cart'), $this->isSecure());
        $this->messageManager->expects($this->once())->method(self::ADD_NOTICE_MESSAGE)->with($this->equalTo('The payment process was not finished successful.'));
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
     * @return \PHPUnit_Framework_Constraint_IsEqual
     */
    private function isSecure()
    {
        return $this->equalTo(['_secure' => true]);
    }
}
