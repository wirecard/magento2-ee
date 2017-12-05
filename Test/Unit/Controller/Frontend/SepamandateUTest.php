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

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Wirecard\ElasticEngine\Controller\Frontend\Sepamandate;

class SepamandateUTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PageFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultFactory;

    /**
     * @var Page|\PHPUnit_Framework_MockObject_MockObject
     */
    private $page;

    /**
     * @var Layout|\PHPUnit_Framework_MockObject_MockObject
     */
    private $layout;

    /**
     * @var AbstractBlock|\PHPUnit_Framework_MockObject_MockObject
     */
    private $block;

    /**
     * @var $context Context|\PHPUnit_Framework_MockObject_MockObject
     */
    private $context;

    public function setUp()
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->page = $this->getMockWithoutInvokingTheOriginalConstructor(Page::class);
        $this->layout = $this->getMockWithoutInvokingTheOriginalConstructor(Layout::class);
        $this->block = $this->getMockWithoutInvokingTheOriginalConstructor(AbstractBlock::class);

        $this->layout->method('getBlock')->with('frontend.sepamandate')->willReturn($this->block);
        $this->page->method('getLayout')->willReturn($this->layout);

        $this->resultFactory = $this->getMockWithoutInvokingTheOriginalConstructor(PageFactory::class);
        $this->resultFactory->method('create')->willReturn($this->page);
    }

    public function testExecute()
    {
        $prov = new Sepamandate($this->context, $this->resultFactory);
        $result = $prov->execute();

        $this->assertEquals($this->page, $result);
    }
}
