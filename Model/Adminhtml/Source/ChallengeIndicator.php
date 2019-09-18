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
use Wirecard\PaymentSdk\Constant\ChallengeInd;

class ChallengeIndicator implements OptionSourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => ChallengeInd::NO_PREFERENCE,
                'label' => __('config_challenge_no_preference')
            ],
            [
                'value' => ChallengeInd::NO_CHALLENGE,
                'label' => __('config_challenge_no_challenge')
            ],
            [
                'value' => ChallengeInd::CHALLENGE_THREED,
                'label' => __('config_challenge_challenge_threed')
            ]
        ];
    }
}
