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

namespace Wirecard\ElasticEngine\Test\Unit\Block\Widget;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Framework\App\State;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Url;
use Magento\Framework\View\Element\Template\File\Resolver;
use Magento\Framework\View\Element\Template\File\Validator;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\EventManager;
use Monolog\Logger;
use Wirecard\ElasticEngine\Block\Widget\TestCredentialsButton;

class TestCredentialsButtonTest extends \PHPUnit_Framework_TestCase
{
    const BUTTON = '<button></button>';
    /**
     * @var TestCredentialsButton
     */
    private $instance;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    public function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $directory = $this->getMockForAbstractClass(ReadInterface::class);
        $filesystem = $this->getMock(Filesystem::class, ['getDirectoryRead'], [], '', false);
        $filesystem->method('getDirectoryRead')->willReturn($directory);
        $eventManager = $this->getMock(EventManager::class, ['dispatch'], [], '', false);
        $appState = $this->getMock(State::class, ['getAreaCode'], [], '', false);
        $resolver = $this->getMock(Resolver::class, ['getTemplateFileName'], [], '', false);
        $validator = $this->getMock(Validator::class, ['isValid'], [], '',
            false);
        $logger = $this->getMock(Logger::class, ['critical'], [], '', false);
        $urlBuilder = $this->getMock(Url::class, ['getUrl'], [], '', false);

        $context = $this->getMock(Context::class, [
            'getFilesystem',
            'getEventManager',
            'getAppState',
            'getResolver',
            'getValidator',
            'getLogger',
            'getUrlBuilder'
        ], [], '', false);
        $context->method('getFilesystem')->willReturn($filesystem);
        $context->method('getEventManager')->willReturn($eventManager);
        $context->method('getAppState')->willReturn($appState);
        $context->method('getResolver')->willReturn($resolver);
        $context->method('getValidator')->willReturn($validator);
        $context->method('getLogger')->willReturn($logger);
        $context->method('getUrlBuilder')->willReturn($urlBuilder);

        $data = [
            'context' => $context,
        ];

        $this->instance = $this->objectManager->getObject(TestCredentialsButton::class, $data);

        $layoutMock = $this->getMockBuilder('Magento\Framework\View\Layout')
            ->disableOriginalConstructor()
            ->getMock();

        $button = $this->getMockBuilder(Button::class)
            ->disableOriginalConstructor()
            ->getMock();
        $button->method('setData')->willReturn($button);
        $button->method('toHtml')->willReturn(self::BUTTON);

        $layoutMock->method('createBlock')->willReturn($button);

        /** @var $layoutMock LayoutInterface */
        $this->instance->setLayout($layoutMock);
    }

    public function testConstructor()
    {
        $expected = 'Wirecard_ElasticEngine::system/config/form/test_credentials_button.phtml';
        $this->assertEquals($expected, $this->instance->getTemplate());
    }

    public function testRender()
    {
        $element = $this->getMock(AbstractElement::class, ['unScope', 'getHtmlId', 'getScope', 'getLabel'], [], '',
            false);

        /** @var $element AbstractElement */
        $this->assertEquals('<tr id="row_"><td class="label"><label for=""><span></span></label></td><td class="value"></td><td class=""></td></tr>',
            $this->instance->render($element));
    }

    public function testGetAjaxUrl()
    {
        $this->assertEquals(null, $this->instance->getAjaxUrl());
    }

    public function testGetButtonHtml()
    {
        $this->assertEquals(self::BUTTON, $this->instance->getButtonHtml());
    }
}
