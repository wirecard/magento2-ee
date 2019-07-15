<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class PoiPiaAction implements ArrayInterface
{
    const INVOICE = 'invoice';
    const ADVANCE = 'advance';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::INVOICE,
                'label' => __('text_payment_type_poi')
            ],
            [
                'value' => self::ADVANCE,
                'label' => __('text_payment_type_pia')
            ]
        ];
    }
}
