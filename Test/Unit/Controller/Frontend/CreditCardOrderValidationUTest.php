<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace WirecardTest\ElasticEngine\Unit\Controller\Frontend;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\Quote;
use Wirecard\ElasticEngine\Controller\Frontend\CreditCardOrderValidation;

class CreditCardOrderValidationUTest extends \PHPUnit_Framework_TestCase
{
    /** @var CreditCardOrderValidation */
    private $controller;

    /** @var Json */
    private $resultJson;

    /** @var Session */
    private $checkoutSession;

    public function testWithValidAmounts()
    {
        $this->initController([
            CreditCardOrderValidation::MAGENTO_GRANT_TOTAL => [
                CreditCardOrderValidation::MAGENTO_VALUE => 120.2
            ]
        ]);

        $expectedResultData = [
                CreditCardOrderValidation::FRONTEND_VALIDATION_KEY => true
        ];
        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with($this->equalTo($expectedResultData));
        $result = $this->controller->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testWithSessionManipulation()
    {
        $this->initController([
            CreditCardOrderValidation::MAGENTO_GRANT_TOTAL => [
                CreditCardOrderValidation::MAGENTO_VALUE => 150.2
            ]
        ]);

        $expectedResultData = [
            CreditCardOrderValidation::FRONTEND_VALIDATION_KEY => false
        ];

        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with($this->equalTo($expectedResultData));


        $result = $this->controller->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    private function setupSessionMock($sessionTotals)
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->once())
            ->method('getTotals')
            ->willReturn($sessionTotals);

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSession->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);
    }

    private function initController($sessionTotals)
    {
        $requestParams = [
            CreditCardOrderValidation::FRONTEND_AMOUNT_KEY => 120.2
        ];
        $requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($requestParams);

        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->once())
            ->method('getRequest')
            ->willReturn($requestMock);

        $this->setupSessionMock($sessionTotals);

        $this->resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()->getMock();
        $resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->resultJson);

        $this->controller = new CreditCardOrderValidation($context, $resultJsonFactory, $this->checkoutSession);
    }
}
