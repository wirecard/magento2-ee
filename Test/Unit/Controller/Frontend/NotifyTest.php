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

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Controller\Frontend\Notify;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Status;
use Wirecard\PaymentSdk\Entity\StatusCollection;
use Wirecard\PaymentSdk\Exception\MalformedResponseException;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;

class NotifyTest extends \PHPUnit_Framework_TestCase
{
    const HANDLE_NOTIFICATION = 'handleNotification';
    const GET_CUSTOM_FIELDS = 'getCustomFields';
    /**
     * @var Notify
     */
    private $controller;

    /**
     * @var TransactionService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transactionService;

    /**
     * @var CustomFieldCollection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customFields;

    /**
     * @var OrderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $order;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    public function setUp()
    {
        /**
         * @var $context Context|\PHPUnit_Framework_MockObject_MockObject
         */
        $context = $this->getMockWithoutInvokingTheOriginalConstructor(Context::class);

        /**
         * @var $httpRequest Http|\PHPUnit_Framework_MockObject_MockObject
         */
        $httpRequest = $this->getMockWithoutInvokingTheOriginalConstructor(Http::class);
        $httpRequest->method('getContent')->willReturn('PayLoad');
        $context->method('getRequest')->willReturn($httpRequest);

        /**
         * @var $transactionServiceFactory TransactionServiceFactory|\PHPUnit_Framework_MockObject_MockObject
         */
        $transactionServiceFactory = $this->getMockWithoutInvokingTheOriginalConstructor(TransactionServiceFactory::class);

        $this->transactionService = $this->getMockWithoutInvokingTheOriginalConstructor(TransactionService::class);

        $transactionServiceFactory->method('create')->willReturn($this->transactionService);

        /**
         * @var $orderRepository OrderRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
         */
        $orderRepository = $this->getMock(OrderRepositoryInterface::class);
        $this->order = $this->getMockWithoutInvokingTheOriginalConstructor(OrderInterface::class);
        $payment = $this->getMockWithoutInvokingTheOriginalConstructor(Payment::class);
        $this->order->method('getPayment')->willReturn($payment);
        $orderRepository->method('get')->willReturn($this->order);

        $this->logger = $this->getMock(LoggerInterface::class);

        $this->customFields = $this->getMock(CustomFieldCollection::class);
        $this->customFields->method('get')->with($this->equalTo('orderId'))->willReturn(42);

        $this->controller = new Notify(
            $context, $transactionServiceFactory,
            $orderRepository, $this->logger);
    }

    public function testExecuteWithSuccessResponse()
    {
        $successResponse = $this->getMockWithoutInvokingTheOriginalConstructor(SuccessResponse::class);
        $successResponse->method(self::GET_CUSTOM_FIELDS)->willReturn($this->customFields);
        $successResponse->method('getProviderTransactionId')->willReturn(1234);
        $this->transactionService->expects($this->once())->method(self::HANDLE_NOTIFICATION)->willReturn($successResponse);

        $this->order->expects($this->once())->method('setStatus')->with('processing');
        $this->order->expects($this->once())->method('setState')->with('processing');
        $this->controller->execute();
    }

    public function testExecuteWithFailureResponse()
    {
        $failureResponse = $this->getMockWithoutInvokingTheOriginalConstructor(FailureResponse::class);
        $failureResponse->method(self::GET_CUSTOM_FIELDS)->willReturn($this->customFields);
        $statusCollection = $this->getMock(StatusCollection::class);
        $status = $this->getMockWithoutInvokingTheOriginalConstructor(Status::class);
        $iterator = new \ArrayIterator([$status, $status]);
        $statusCollection->method('getIterator')->willReturn($iterator);
        $failureResponse->expects($this->once())->method('getStatusCollection')->willReturn($statusCollection);

        $this->transactionService->expects($this->once())->method(self::HANDLE_NOTIFICATION)->willReturn($failureResponse);
        $this->order->expects($this->once())->method('setStatus')->with('payment_review');
        $this->order->expects($this->once())->method('setState')->with('payment_review');
        $this->controller->execute();
    }

    /**
     * @expectedException \Wirecard\PaymentSdk\Exception\MalformedResponseException
     */
    public function testExecuteWithMalformedPayload()
    {
        $this->transactionService
            ->expects($this->once())
            ->method(self::HANDLE_NOTIFICATION)
            ->willThrowException(new MalformedResponseException('Message'));

        $this->logger->expects($this->once())->method('error')->with('Response is malformed: Message');

        $this->controller->execute();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExecuteWithInvalidArgument()
    {
        $this->transactionService
            ->expects($this->once())
            ->method(self::HANDLE_NOTIFICATION)
            ->willThrowException(new \InvalidArgumentException('Message'));

        $this->logger->expects($this->once())->method('error')->with('Invalid argument set: Message');

        $this->controller->execute();
    }

    public function testExecuteWithUnexpecterResponseObject()
    {
        //this Response will never happen on notificy call
        $unexpectedResponse = $this->getMockWithoutInvokingTheOriginalConstructor(InteractionResponse::class);
        $unexpectedResponse->method(self::GET_CUSTOM_FIELDS)->willReturn($this->customFields);

        $this->transactionService->expects($this->once())->method(self::HANDLE_NOTIFICATION)->willReturn($unexpectedResponse);
        $this->controller->execute();
    }
}
