<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Block\Adminhtml\Support;

use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Backend\Block\Widget\Context;
use Wirecard\ElasticEngine\Block\Adminhtml\Support\Contact;

require __DIR__ . '/../../../../Stubs/AbstractBlock.php';

class ContactUTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->markTestSkipped('ObjectManager Unit Helper needs newer PHPUnit');
    }

    public function testConstructor()
    {
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $buttonList = $this->getMockBuilder(ButtonList::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->method('getButtonList')
            ->willReturn($buttonList);

        new Contact($context);
    }
}
