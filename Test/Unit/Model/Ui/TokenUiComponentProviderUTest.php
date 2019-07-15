<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Model\Ui;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Wirecard\ElasticEngine\Model\Ui\ConfigProvider;
use Wirecard\ElasticEngine\Model\Ui\TokenUiComponentProvider;

class TokenUiComponentProviderUTest extends \PHPUnit_Framework_TestCase
{
    const HASH = 'my-long-hash-123123123123';

    /**
     * @var TokenUiComponentInterfaceFactory $componentFactory
     */
    private $componentFactory;

    /**
     * @var PaymentTokenInterface $paymentToken
     */
    private $paymentToken;

    /**
     * @var array
     */
    private $component;

    public function setUp()
    {
        $details = '{
            "type": "MC",
            "maskedCC": "1234",
            "expirationDate": "xx-xxxx"
        }';

        $this->component = [
            'config' => [
                'code' => ConfigProvider::CREDITCARD_VAULT_CODE,
                TokenUiComponentProviderInterface::COMPONENT_DETAILS => $details,
                TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => self::HASH
            ],
            'name' => 'Wirecard_ElasticEngine/js/view/payment/method-renderer/vault'
        ];

        $this->componentFactory = $this->getMockBuilder(TokenUiComponentInterfaceFactory::class)->disableOriginalConstructor()->setMethods(['create'])->getMock();
        $this->componentFactory->method('create')->willReturn($this->component);

        $this->paymentToken = $this->getMockBuilder(PaymentTokenInterface::class)->disableOriginalConstructor()->getMock();
        $this->paymentToken->method('getTokenDetails')->willReturn($details);
        $this->paymentToken->method('getPublicHash')->willReturn(self::HASH);
    }

    public function testGetComponentForToken()
    {
        $conf = new TokenUiComponentProvider($this->componentFactory);

        $this->assertEquals($this->component, $conf->getComponentForToken($this->paymentToken));
    }
}
