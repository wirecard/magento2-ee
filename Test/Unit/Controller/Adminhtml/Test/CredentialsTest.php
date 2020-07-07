<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
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
 * This class tests configuration
 *
 * Class CredentialsTest
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
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $authorization = $this->getMockBuilder(Authorization::class)
            ->disableOriginalConstructor()
            ->setMethods(['isAllowed'])
            ->getMock();
        $authorization->method('isAllowed')
            ->willReturn(true);
        $context->method('getAuthorization')
            ->willReturn($authorization);

        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParams'])
            ->getMock();
        $context->method('getRequest')
            ->willReturn($this->request);

        $this->json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();
        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->resultJsonFactory->method('create')
            ->willReturn($this->json);

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        /** @var Context $context */
        $this->instance = new Credentials($context, $this->resultJsonFactory, $this->logger);
    }

    public function testConstructor()
    {
        $this->assertAttributeEquals(
            $this->resultJsonFactory,
            'resultJsonFactory',
            $this->instance
        );
    }

    public function testExecuteProvider()
    {
        return [
            ['https://api-test.wirecard.com']
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
