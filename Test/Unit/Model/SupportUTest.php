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
        $transportInterface = $this->getMockBuilder(\Magento\Framework\Mail\TransportInterface::class)->disableOriginalConstructor()->getMock();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();

        $this->transportBuilder = $this->getMockBuilder(TransportBuilder::class)->disableOriginalConstructor()->getMock();
        $this->transportBuilder->method('setTemplateIdentifier')->willReturn($this->transportBuilder);
        $this->transportBuilder->method('setTemplateOptions')->willReturn($this->transportBuilder);
        $this->transportBuilder->method('setTemplateVars')->willReturn($this->transportBuilder);
        $this->transportBuilder->method('setFrom')->willReturn($this->transportBuilder);
        $this->transportBuilder->method('addTo')->willReturn($this->transportBuilder);
        $this->transportBuilder->method('getTransport')->willReturn($transportInterface);

        $this->moduleLoader = $this->getMockBuilder(Loader::class)->disableOriginalConstructor()->getMock();
        $this->moduleLoader->method('load')->willReturn(['Module1' => ['name' => 'Magento_TestModule']]);

        $this->config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $this->config->method('getActiveMethods')->willReturn([
            'wirecard_elasticengine_paypal' => []
        ]);

        $this->moduleListInterface = $this->getMockBuilder(ModuleListInterface::class)->disableOriginalConstructor()->getMock();

        $this->productMetadata = $this->getMockBuilder(ProductMetadata::class)->disableOriginalConstructor()->getMock();

        $this->postObject = new DataObject();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Please enter a valid e-mail address.
     */
    public function testMissingTo()
    {
        $this->support = new Support(
            $this->scopeConfig,
            $this->transportBuilder,
            $this->moduleLoader,
            $this->config,
            $this->moduleListInterface,
            $this->productMetadata
        );
        $this->support->sendrequest($this->postObject);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Please enter a valid e-mail address (reply to).
     */
    public function testInvalidReplytoEmail()
    {
        $this->support = new Support(
            $this->scopeConfig,
            $this->transportBuilder,
            $this->moduleLoader,
            $this->config,
            $this->moduleListInterface,
            $this->productMetadata
        );
        $this->postObject->addData(['to' => 'email@address.com', 'replyto' => 'e@a']);
        $this->support->sendrequest($this->postObject);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Please set your shop e-mail address!
     */
    public function testMissingShopEmail()
    {
        $this->support = new Support(
            $this->scopeConfig,
            $this->transportBuilder,
            $this->moduleLoader,
            $this->config,
            $this->moduleListInterface,
            $this->productMetadata
        );
        $this->postObject->addData(['to' => 'email@address.com', 'replyto' => 'email@address.com']);
        $this->support->sendrequest($this->postObject);
    }

    public function testSendrequest()
    {
        $this->scopeConfig->method('getValue')->will($this->returnCallback([$this, 'configValueMap']));

        $this->support = new Support(
            $this->scopeConfig,
            $this->transportBuilder,
            $this->moduleLoader,
            $this->config,
            $this->moduleListInterface,
            $this->productMetadata
        );
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
}
