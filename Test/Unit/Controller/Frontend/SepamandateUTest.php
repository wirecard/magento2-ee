<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Controller\Frontend;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Result\Page;
use Wirecard\ElasticEngine\Controller\Frontend\Sepamandate;

class SepamandateUTest extends \PHPUnit_Framework_TestCase
{
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

        $this->page = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->layout = $this->getMockBuilder(Layout::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->block = $this->getMockBuilder(AbstractBlock::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->layout->method('getBlock')
            ->with('frontend.sepamandate')
            ->willReturn($this->block);
        $this->page->method('getLayout')
            ->willReturn($this->layout);

        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->resultFactory->method('create')
            ->willReturn($this->page);

        $this->context->method('getResultFactory')
            ->willReturn($this->resultFactory);
    }

    public function testExecute()
    {
        $prov = new Sepamandate($this->context);
        $result = $prov->execute();

        $this->assertEquals($this->page, $result);
    }
}
