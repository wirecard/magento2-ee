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
