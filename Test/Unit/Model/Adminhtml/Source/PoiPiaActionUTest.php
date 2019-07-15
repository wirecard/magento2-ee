<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Model\Adminhtml\Source;

use Wirecard\ElasticEngine\Model\Adminhtml\Source\PoiPiaAction;

class PoiPiaActionUTest extends \PHPUnit_Framework_TestCase
{
    public function testToOptionArray()
    {
        $poiPiaAction = new PoiPiaAction();

        $actionArray = [
            [
                'value' => 'invoice',
                'label' => 'text_payment_type_poi'
            ],
            [
                'value' => 'advance',
                'label' => 'text_payment_type_pia'
            ]
        ];

        $this->assertEquals($poiPiaAction->toOptionArray(), $actionArray);
    }
}
