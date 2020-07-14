<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Block\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;
use Wirecard\ElasticEngine\Block\Checkout\SepaMandateBlock;

class SepaMandateBlockUTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sepaConfig;

    /**
     * @var SepaMandateBlock|\PHPUnit_Framework_MockObject_MockObject
     */
    private $block;

    public function setUp()
    {
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sepaConfig = $this->getMockBuilder(ConfigInterface::class)
            ->getMock();

        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->block = new SepaMandateBlock($context, $this->sepaConfig, $session, []);
    }

    public function testGetCreditorId()
    {
        $this->sepaConfig->method('getValue')
            ->with('creditor_id')
            ->willReturn('CREDITOR ID');
        $this->assertEquals('CREDITOR ID', $this->block->getCreditorId());
    }

    public function testGetCreditorName()
    {
        $this->sepaConfig->method('getValue')->with('creditor_name')
            ->willReturn('CREDITOR NAME');
        $this->assertEquals('CREDITOR NAME', $this->block->getCreditorName());
    }

    public function testGetStoreCity()
    {
        $this->sepaConfig->method('getValue')->with('creditor_city')
            ->willReturn('CREDITOR CITY');
        $this->assertEquals('CREDITOR CITY', $this->block->getStoreCity());
    }

    public function testGetEnabledBic()
    {
        $this->sepaConfig->method('getValue')->with('enable_bic')
            ->willReturn('ENABLED BIC');
        $this->assertEquals('ENABLED BIC', $this->block->getBankBicEnabled());
    }

    public function testGetMandateId()
    {
        $this->assertEquals('--' . strtotime(date("Y-m-d H:i:s")), $this->block->getMandateId());
    }
}
