<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Block\Checkout;

use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;
use Wirecard\ElasticEngine\Block\Checkout\PaymentPageLoader;

class PaymentPageLoaderUTest extends \PHPUnit_Framework_TestCase
{
    const BASE_URL = 'http://base.url';

    public function testGetPaymentPageLoaderUrl()
    {
        $context = $this->getMockWithoutInvokingTheOriginalConstructor(Context::class);

        $eeConfig = $this->getMock(ConfigInterface::class);
        $eeConfig->method('getValue')->withConsecutive(
            ['credentials/base_url'],
            ['credentials/http_user'],
            ['credentials/http_pass'],
            ['settings/public_key']
        )->willReturnOnConsecutiveCalls(self::BASE_URL, 'user', 'pass', 'public_key');

        $paymentPageLoader = new PaymentPageLoader($context, $eeConfig);
        $this->assertEquals(self::BASE_URL . '/engine/hpp/paymentPageLoader.js', $paymentPageLoader->getPaymentPageLoaderUrl());
    }
}
