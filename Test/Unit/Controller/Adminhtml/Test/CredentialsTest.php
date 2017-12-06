<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
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

namespace Wirecard\ElasticEngine\Test\Unit\Adminhtml\Test;

use GuzzleHttp\Psr7\Request;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Authorization;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Controller\Adminhtml\Test\Credentials;

/**
 * Class CredentialsTest
 * @package Wirecard\ElasticEngine\Test\Unit\Adminhtml\Test
 * @method _isAllowed()
 */
class CredentialsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Credentials
     */
    private $instance;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $json;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function setUp()
    {
        $context = $this->getMock(Context::class, [], [], '', false);

        $authorization = $this->getMock(Authorization::class, ['isAllowed'], [], '', false);
        $authorization->method('isAllowed')->willReturn(true);
        $context->method('getAuthorization')->willReturn($authorization);

        $this->request = $this->getMock(Request::class, ['getParams'], [], '', false);
        $context->method('getRequest')->willReturn($this->request);

        $this->json = $this->getMock(Json::class, ['setData'], [], '', false);
        $this->resultJsonFactory = $this->getMock(JsonFactory::class, ['create'], [], '', false);
        $this->resultJsonFactory->method('create')->willReturn($this->json);

        $this->logger = $this->getMock(LoggerInterface::class);

        /** @var Context $context */
        $this->instance = new Credentials($context, $this->resultJsonFactory, $this->logger);
    }

    public function testConstructor()
    {
        $this->assertAttributeEquals($this->resultJsonFactory, 'resultJsonFactory', $this->instance);
    }

    public function testExecuteProvider()
    {
        return [
            ['https://api-test.wirecard.com'],
            ['http://localhost']
        ];
    }

    /**
     * @dataProvider testExecuteProvider
     * @param string $url
     */
    public function testExecute($url)
    {
        $this->request->method('getParams')->willReturn([
            'baseUrl' => $url,
            'httpUser' => '70000-APITEST-AP',
            'httpPass' => 'qD2wzQ_hrc!8'
        ]);
        $this->json->expects($this->once())->method('setData');
        $this->instance->execute();
    }

    public function testIsAllowed()
    {
        $helper = function () {
            return $this->_isAllowed();
        };

        $bound = $helper->bindTo($this->instance, $this->instance);

        $this->assertTrue($bound());
    }
}
