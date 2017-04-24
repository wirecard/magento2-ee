<?php
/**
 * Created by IntelliJ IDEA.
 * User: timon.roenisch
 * Date: 24.04.2017
 * Time: 08:46
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
                'label' => 'Authorize'
            ],
            [
                'value' => 'authorize_capture',
                'label' => 'Capture'
            ]
        ];

        $this->assertEquals($paymentAction->toOptionArray(), $actionArray);
    }
}