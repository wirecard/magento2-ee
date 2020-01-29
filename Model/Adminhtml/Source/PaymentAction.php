<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PaymentAction implements OptionSourceInterface
{
    const AUTHORIZE='authorize';
    const AUTHORIZE_CAPTURE='authorize_capture';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::AUTHORIZE,
                'label' => __('text_payment_action_reserve')
            ],
            [
                'value' => self::AUTHORIZE_CAPTURE,
                'label' => __('text_payment_action_pay')
            ]
        ];
    }
}
