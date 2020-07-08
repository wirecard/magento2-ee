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
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Sales\Model\Order;
use Wirecard\ElasticEngine\Block\Checkout\PoiPiaBlock;

class PoiPIaBlockUTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var PoiPiaBlock|\PHPUnit_Framework_MockObject_MockObject
     */
    private $block;

    private $methodConfig;

    public function setUp()
    {
        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->methodConfig = $this->getMockBuilder(ConfigInterface::class)->getMock();

        $payment = $this->getMockBuilder(Order\Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getAdditionalInformation')->willReturn(
            [
                'merchant-bank-account.0.iban' => 'IB0123456789',
                'merchant-bank-account.0.bic' => 'biccc',
                'provider-transaction-reference-id' => 'P0T1R2I3D4'
            ]
        );

        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getPayment')->willReturn($payment);

        $session = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $session->method('getLastRealOrder')->willReturn($order);

        $pricingHelper = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();
        $pricingHelper->method('currency')->willReturn("€30.5");

        $this->block = new PoiPiaBlock($context, $session, $pricingHelper, $this->methodConfig, []);
    }

    public function testGetMerchantBankAccount()
    {
        $this->assertEquals(['iban' => 'IB0123456789', 'bic' => 'biccc'], $this->block->getMerchantBankAccount());
    }

    public function testGetPtrid()
    {
        $this->assertEquals('P0T1R2I3D4', $this->block->getPtrid());
    }

    public function testIsPia()
    {
        $this->methodConfig->method('getValue')->with('poipia_action')->willReturn('advance');
        $this->assertTrue($this->block->isPia());
    }

    public function testIsPoi()
    {
        $this->methodConfig->method('getValue')->with('poipia_action')->willReturn('invoice');
        $this->assertFalse($this->block->isPia());
    }

    public function testGetAmount()
    {
        $this->assertEquals("€30.5", $this->block->getAmount());
    }
}
