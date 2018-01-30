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
        $httpRequest = $this->getMockBuilder(Http::class)->disableOriginalConstructor()->setMethods(['getParam'])->getMock();
        $httpRequest->method('getParam')->willReturn(self::HASH);

        $urlBuilder = $this->getMock(UrlInterface::class);
        $urlBuilder->method('getRouteUrl')->willReturn('http://magen.to/');

        $this->context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->setMethods(['getRequest', 'getUrl', 'getResultFactory'])->getMock();
        $this->context->method('getRequest')->willReturn($httpRequest);
        $this->context->method('getUrl')->willReturn($urlBuilder);

        $this->customerSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $this->customerSession->method('getCustomerId')->willReturn(1);

        $this->paymentTokenInterface = $this->getMockBuilder(PaymentTokenInterface::class)->disableOriginalConstructor()->getMock();
        $this->paymentTokenInterface->method('getGatewayToken')->willReturn('12345');
        $this->paymentTokenInterface->method('getPaymentMethodCode')->willReturn('Wirecard');

        $this->paymentTokenManagement = $this->getMockBuilder(PaymentTokenManagementInterface::class)->disableOriginalConstructor()->getMock();
        $this->paymentTokenManagement->method('getByPublicHash')->willReturn($this->paymentTokenInterface);

        $inline = $this->getMockForAbstractClass(InlineInterface::class);
        $inline->method('processResponseBody')->willReturn(null);

        $json = new Json($inline);

        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)->disableOriginalConstructor()->setMethods(['create'])->getMock();
        $this->resultFactory->method('create')->willReturn($json);
        $this->context->method('getResultFactory')->willReturn($this->resultFactory);
    }

    public function testExecute()
    {
        $vault = new Vault($this->context, $this->paymentTokenManagement, $this->customerSession);

        $inline = $this->getMockForAbstractClass(InlineInterface::class);
        $inline->method('processResponseBody')->willReturn(null);

        $result = new Json($inline);
        $result->setData(["token_id" => "12345", "method_code" => "Wirecard"]);

        $this->assertEquals($result, $vault->execute());
    }
}
