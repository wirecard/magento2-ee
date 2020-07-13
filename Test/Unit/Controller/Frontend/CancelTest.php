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
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Wirecard\ElasticEngine\Controller\Frontend\Cancel;

class CancelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerInterface|\PHPUnit_Framework_MockObject_MockObject $messageManager
     */
    private $messageManager;

    /**
     * @var Redirect|\PHPUnit_Framework_MockObject_MockObject
     */
    private $redirectResult;

    /**
     * @var OrderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $order;

    /**
     * @var Cancel
     */
    private $controller;

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
        $resultFactory->method('create')
            ->willReturn($this->redirectResult);
        $context->method('getResultFactory')
            ->willReturn($resultFactory);

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->getMock();
        $context->method('getMessageManager')
            ->willReturn($this->messageManager);

        /**
         * @var $orderRepository OrderRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
         */
        $orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->getMock();
        $this->order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderRepository->method('get')
            ->willReturn($this->order);

        /**
         * @var $checkoutSession Session|\PHPUnit_Framework_MockObject_MockObject
         */
        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $checkoutSession->expects($this->once())
            ->method('restoreQuote');
        $checkoutSession->expects($this->once())
            ->method('getLastRealOrder')->willReturn($this->order);

        $this->controller = new Cancel($context, $checkoutSession, $orderRepository);
    }

    public function testExecute()
    {
        $this->order->expects($this->once())->method('cancel');
        $this->redirectResult->expects($this->once())
            ->method('setPath')->with('checkout/cart');
        $this->messageManager->expects($this->once())
            ->method('addNoticeMessage')->with('canceled_payment_process');
        $result = $this->controller->execute();
        $this->assertEquals($result, $this->redirectResult);
    }
}
