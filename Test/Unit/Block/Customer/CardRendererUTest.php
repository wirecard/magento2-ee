<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
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

        $this->token = $this->getMockBuilder(PaymentTokenInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentMethodCode', 'getTokenDetails'])
            ->getMockForAbstractClass();
        $this->token->method('getPaymentMethodCode')
            ->willReturn('wirecard_elasticengine_creditcard');
        $this->token->method('getTokenDetails')
            ->willReturn(json_encode($tokenDetails));

        $this->cardRenderer = $this->getMockBuilder(CardRenderer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTokenDetails', 'getIconForType'])
            ->getMockForAbstractClass();
        $this->cardRenderer->method('getTokenDetails')
            ->willReturn($tokenDetails);
        $this->cardRenderer->method('getIconForType')
            ->willReturn($icon);
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
