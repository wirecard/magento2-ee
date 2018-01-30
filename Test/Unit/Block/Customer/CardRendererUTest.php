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

namespace Wirecard\ElasticEngine\Test\Unit\Customer;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Wirecard\ElasticEngine\Block\Customer\CardRenderer;

class CardRendererUTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CardRenderer
     */
    private $cardRenderer;

    /**
     * @var PaymentTokenInterface
     */
    private $token;

    public function setUp()
    {
        $tokenDetails = [
            "maskedCC" => "1111",
            "expirationDate" => "xx-xxxx",
            "type" => "CC"
        ];

        $icon = [
            'url' => 'myurl',
            'width' => 20,
            'height' => 20
        ];

        $this->token = $this->getMockBuilder(PaymentTokenInterface::class)->disableOriginalConstructor()->setMethods(['getPaymentMethodCode', 'getTokenDetails'])->getMockForAbstractClass();
        $this->token->method('getPaymentMethodCode')->willReturn('wirecard_elasticengine_creditcard');
        $this->token->method('getTokenDetails')->willReturn(json_encode($tokenDetails));

        $this->cardRenderer = $this->getMock(CardRenderer::class, ['getTokenDetails', 'getIconForType'], [], '', false);
        $this->cardRenderer->method('getTokenDetails')->willReturn($tokenDetails);
        $this->cardRenderer->method('getIconForType')->willReturn($icon);
    }

    public function testCardRenderer()
    {
        $this->assertEquals(true, $this->cardRenderer->canRender($this->token));
        $this->assertEquals("1111", $this->cardRenderer->getNumberLast4Digits());
        $this->assertEquals("xx-xxxx", $this->cardRenderer->getExpDate());
        $this->assertEquals("myurl", $this->cardRenderer->getIconUrl());
        $this->assertEquals(20, $this->cardRenderer->getIconHeight());
        $this->assertEquals(20, $this->cardRenderer->getIconWidth());
    }
}
