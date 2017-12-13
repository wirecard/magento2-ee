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

namespace Wirecard\ElasticEngine\Test\Unit\Block\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Wirecard\ElasticEngine\Block\Checkout\PoiPiaBlock;

class PoiPIaBlockUTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var PoiPiaBlock|\PHPUnit_Framework_MockObject_MockObject
     */
    private $block;

    public function setUp()
    {
        $context = $this->getMock(Context::class, [], [], '', false);

        $payment = $this->getMock(Order\Payment::class, [], [], '', false);
        $payment->method('getAdditionalInformation')->willReturn([
            'data' => [
                'merchant-bank-account.0.iban' => 'IB0123456789',
                'merchant-bank-account.0.bic' => 'biccc',
                'provider-transaction-reference-id' => 'P0T1R2I3D4'
            ]
        ]);

        $order = $this->getMock(Order::class, [], [], '', false);
        $order->method('getPayment')->willReturn($payment);

        $session = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $session->method('getLastRealOrder')->willReturn($order);

        $this->block = new PoiPiaBlock($context, $session, []);
    }

    public function testGetMerchantBankAccount()
    {
        $this->assertEquals(['iban' => 'IB0123456789', 'bic' => 'biccc'], $this->block->getMerchantBankAccount());
    }

    public function testGetPtrid()
    {
        $this->assertEquals('P0T1R2I3D4', $this->block->getPtrid());
    }

    public function testIsPoiPia()
    {
        $this->assertTrue($this->block->isPoiPia());
    }

}
