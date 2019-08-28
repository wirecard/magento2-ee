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

class ChallengeIndicator implements ArrayInterface
{
    const NO_PREFERENCE = 'no_preference';
    const NO_CHALLENGE = 'no_challenge';
    const CHALLENGE_THREED = 'challenge_threed';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::NO_PREFERENCE,
                'label' => __('config_challenge_no_preference')
            ],
            [
                'value' => self::NO_CHALLENGE,
                'label' => __('config_challenge_no_challenge')
            ],
            [
                'value' => self::CHALLENGE_THREED,
                'label' => __('config_challenge_challenge_threed')
            ]
        ];
    }
}
