<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\DataObject;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Module\ModuleList\Loader;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Payment\Model\Config;
use Wirecard\ElasticEngine\Model\Adminhtml\Support;

class SupportUtest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Support $support
     */
    private $support;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject $scopeConfig
     */
    private $scopeConfig;

    /**
     * @var TransportBuilder|\PHPUnit_Framework_MockObject_MockObject $scopeConfig
     */
    private $transportBuilder;

    /**
     * @var Loader|\PHPUnit_Framework_MockObject_MockObject $scopeConfig
     */
    private $moduleLoader;

    /**
     * @var ModuleListInterface|\PHPUnit_Framework_MockObject_MockObject $scopeConfig
     */
    private $moduleListInterface;

    /**
     * @var ProductMetadata|\PHPUnit_Framework_MockObject_MockObject $scopeConfig
     */
    private $productMetadata;

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject $scopeConfig
     */
    private $config;

    /**
     * @var DataObject
     */
    private $postObject;

    public function setUp()
    {
        $transportInterface = $this->getMockBuilder(\Magento\Framework\Mail\TransportInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transportBuilder = $this->getMockBuilder(TransportBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transportBuilder->method('setTemplateIdentifier')
            ->willReturn($this->transportBuilder);
        $this->transportBuilder->method('setTemplateOptions')
            ->willReturn($this->transportBuilder);
        $this->transportBuilder->method('setTemplateVars')
            ->willReturn($this->transportBuilder);
        $this->transportBuilder->method('setFrom')
            ->willReturn($this->transportBuilder);
        $this->transportBuilder->method('addTo')
            ->willReturn($this->transportBuilder);
        $this->transportBuilder->method('getTransport')
            ->willReturn($transportInterface);

        $this->moduleLoader = $this->getMockBuilder(Loader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->moduleLoader->method('load')
            ->willReturn(['Module1' => ['name' => 'Magento_TestModule']]);

        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->config->method('getActiveMethods')
            ->willReturn([
            'wirecard_elasticengine_paypal' => []
        ]);

        $this->moduleListInterface = $this->getMockBuilder(ModuleListInterface::class)
            ->getMock();
        $this->moduleListInterface->method('getOne')
            ->with(self::WIRECARD_EE_MODULE_NAME)
            ->willReturn(['setup_version' => self::WIRECARD_EE_VERSION]);

        $this->productMetadata = $this->getMockBuilder(ProductMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->postObject = new DataObject();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage enter_valid_email_error
     */
    public function testMissingTo()
    {
        $this->createSupport();
        $this->support->sendrequest($this->postObject);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage enter_valid_email_error
     */
    public function testInvalidReplytoEmail()
    {
        $this->createSupport();
        $this->postObject->addData(['to' => 'email@address.com', 'replyto' => 'e@a']);
        $this->support->sendrequest($this->postObject);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage enter_valid_email_error
     */
    public function testMissingShopEmail()
    {
        $this->createSupport();
        $this->postObject->addData(['to' => 'email@address.com', 'replyto' => 'email@address.com']);
        $this->support->sendrequest($this->postObject);
    }

    public function testSendrequest()
    {
        $this->scopeConfig->method('getValue')
            ->will($this->returnCallback([$this, 'configValueMap']));

        $this->createSupport();
        $this->postObject->addData(['to' => 'email@address.com', 'replyto' => 'email@address.com']);

        $this->assertTrue($this->support->sendrequest($this->postObject));
    }

    public function configValueMap($key)
    {
        $data = [
            'trans_email/ident_general/email' => 'trans_email@shop.com',
            'trans_email/ident_general/name' => 'shop owner',
            'wirecard_elasticengine/credentials' => [
                'pass' => 'skipped',
                'foo' => 'bar'
            ],
            'payment/wirecard_elasticengine_paypal' => 'Wirecard elastic engine paypal'
        ];

        return $data[$key];
    }

    public function testWhitelistingCredentials()
    {
        $disallowValues = [
            'http_user' => 'x',
            'http_pass'  => 'x',
            'three_d_secret'  => 'x',
            'secret'  => 'x',
            'creditor_id'  => 'x',
            'creditor_name'  => 'x',
            'base_url'  => 'x',
        ];
        $cleanValues = [
            'base_url' => 'x',
        ];
        $this->createSupport();

        $output = $this->invokeMethod($this->support, 'whitelistConfig', [$disallowValues]);
        $this->assertSame($cleanValues, $output, "Whitelisting values for support does not work");
    }

    private function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    private function createSupport()
    {
        $this->support = new Support(
            $this->scopeConfig,
            $this->transportBuilder,
            $this->moduleLoader,
            $this->config,
            $this->moduleListInterface,
            $this->productMetadata
        );
    }
}
