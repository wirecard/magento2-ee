<?php

namespace Wirecard\ElasticEngine\Test\Unit\Block\Adminhtml\Support;

use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Backend\Block\Widget\Context;
use Wirecard\ElasticEngine\Block\Adminhtml\Support\Contact;

require __DIR__ . '/../../../../Stubs/AbstractBlock.php';

class ContactUTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $buttonList = $this->getMockBuilder(ButtonList::class)->disableOriginalConstructor()->getMock();

        $context->method('getButtonList')->willReturn($buttonList);

        new Contact($context);
    }
}
