<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Controller\Frontend;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Wirecard\ElasticEngine\Controller\Frontend\Notify as NotifyController;
use Wirecard\ElasticEngine\Gateway\Model\Notify;
use Wirecard\ElasticEngine\Gateway\Model\TransactionUpdater;
use Wirecard\PaymentSdk\Response\SuccessResponse;

class NotifyTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Notify
     */
    protected $notify;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|TransactionUpdater
     */
    protected $transactionUpdater;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Http
     */
    protected $request;

    /**
     * @var NotifyController
     */
    private $controller;

    public function setUp()
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|Context $context */
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->notify = $this->getMockBuilder(Notify::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionUpdater = $this->getMockBuilder(TransactionUpdater::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContent'])
            ->getMock();

        $context->method('getRequest')->willReturn($this->request);

        $this->controller = new NotifyController($context, $this->notify, $this->transactionUpdater);
    }

    public function testExecuteWithEmptyContent()
    {
        $this->notify->expects($this->never())->method('fromXmlResponse');
        $this->controller->execute();
    }

    public function testExecute()
    {
        $successResponse = $this->getMockBuilder(SuccessResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request->method('getContent')
            ->willReturn('<xml>');
        $this->notify->method('fromXmlResponse')
            ->willReturn($successResponse);
        $this->notify->expects($this->once())
            ->method('process');
        $this->controller->execute();
    }
}
