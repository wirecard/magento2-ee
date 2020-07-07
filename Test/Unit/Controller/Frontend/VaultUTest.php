<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Controller\Frontend;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Translate\InlineInterface;
use Magento\Framework\UrlInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Wirecard\ElasticEngine\Controller\Frontend\Vault;

class VaultUTest extends \PHPUnit_Framework_TestCase
{
    const HASH = "myhash";
    /**
     * @var PaymentTokenManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentTokenManagement;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerSession;

    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    private $context;

    /**
     * @var PaymentTokenInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentTokenInterface;

    /**
     * @var ResultFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultFactory;

    public function setUp()
    {
        $this->markTestSkipped('ObjectManager Unit Helper needs newer PHPUnit');

        $httpRequest = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMock();
        $httpRequest->method('getParam')
            ->willReturn(self::HASH);

        $urlBuilder = $this->getMock(UrlInterface::class);
        $urlBuilder->method('getRouteUrl')
            ->willReturn('http://magen.to/');

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequest', 'getUrl', 'getResultFactory'])
            ->getMock();
        $this->context->method('getRequest')->willReturn($httpRequest);
        $this->context->method('getUrl')->willReturn($urlBuilder);

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerSession->method('getCustomerId')
            ->willReturn(1);

        $this->paymentTokenInterface = $this->getMockBuilder(PaymentTokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentTokenInterface->method('getGatewayToken')
            ->willReturn('12345');
        $this->paymentTokenInterface->method('getPaymentMethodCode')
            ->willReturn('Wirecard');

        $this->paymentTokenManagement = $this->getMockBuilder(PaymentTokenManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentTokenManagement->method('getByPublicHash')
            ->willReturn($this->paymentTokenInterface);

        $inline = $this->getMockForAbstractClass(InlineInterface::class);
        $inline->method('processResponseBody')
            ->willReturn(null);

        $json = new Json($inline);

        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->resultFactory->method('create')
            ->willReturn($json);
        $this->context->method('getResultFactory')
            ->willReturn($this->resultFactory);
    }

    public function testExecute()
    {
        $vault = new Vault($this->context, $this->paymentTokenManagement, $this->customerSession);

        $inline = $this->getMockForAbstractClass(InlineInterface::class);
        $inline->method('processResponseBody')
            ->willReturn(null);

        $result = new Json($inline);
        $result->setData(["token_id" => "12345", "method_code" => "Wirecard"]);

        $this->assertEquals($result, $vault->execute());
    }
}
