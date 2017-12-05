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
        $resultFactory->method('create')->willReturn($this->redirectResult);
        $context->method('getResultFactory')->willReturn($resultFactory);

        $this->messageManager = $this->getMock(ManagerInterface::class);
        $context->method('getMessageManager')->willReturn($this->messageManager);

        /**
         * @var $orderRepository OrderRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
         */
        $orderRepository = $this->getMock(OrderRepositoryInterface::class);
        $this->order = $this->getMockWithoutInvokingTheOriginalConstructor(Order::class);
        $orderRepository->method('get')->willReturn($this->order);

        /**
         * @var $checkoutSession Session|\PHPUnit_Framework_MockObject_MockObject
         */
        $checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $checkoutSession->expects($this->once())->method('restoreQuote');
        $checkoutSession->expects($this->once())->method('getLastRealOrder')->willReturn($this->order);

        $this->controller = new Cancel($context, $checkoutSession, $orderRepository);
    }

    public function testExecute()
    {
        $this->order->expects($this->once())->method('cancel');
        $this->redirectResult->expects($this->once())->method('setPath')->with('checkout/cart');
        $this->messageManager->expects($this->once())->method('addNoticeMessage')->with('You have canceled the payment process.');
        $result = $this->controller->execute();
        $this->assertEquals($result, $this->redirectResult);
    }
}
