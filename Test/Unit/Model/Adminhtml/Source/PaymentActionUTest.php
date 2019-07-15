<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Model\Adminhtml\Source;

use Wirecard\ElasticEngine\Model\Adminhtml\Source\PaymentAction;

class PaymentActionUTest extends \PHPUnit_Framework_TestCase
{
    public function testToOptionArray()
    {
        $paymentAction = new PaymentAction();

        $actionArray = [
            [
                'value' => 'authorize',
                'label' => 'text_payment_action_reserve'
            ],
            [
                'value' => 'authorize_capture',
                'label' => 'text_payment_action_pay'
            ]
        ];

        $this->assertEquals($paymentAction->toOptionArray(), $actionArray);
    }
}
