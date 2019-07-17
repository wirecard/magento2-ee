<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
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
        $validator = $this->getMock(
            Validator::class,
            ['isValid'],
            [],
            '',
            false
        );
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
        $element = $this->getMock(
            AbstractElement::class,
            ['unScope', 'getHtmlId', 'getScope', 'getLabel'],
            [],
            '',
            false
        );

        /** @var $element AbstractElement */
        $this->assertEquals(
            '<tr id="row_"><td class="label"><label for=""><span></span></label></td><td class="value"></td><td class=""></td></tr>',
            $this->instance->render($element)
        );
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
