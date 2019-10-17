<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\tests\_support;

class ActorExtendedWithWrappers extends \Codeception\Actor
{
//    use _generated\ExtendedActorActions;

    /**
     * Method waitForElementClickable
     * @param string $element
     * @param integer $timeout
     * @since 2.2.0
     */
    public function waitForElementClickableWithExtendedTimeout($element, $timeout=60)
    {
        $this->waitForElementClickable($element, $timeout);
    }

    /**
     * Method waitForElementVisible
     * @param string $element
     * @param integer $timeout
     * @since 2.2.0
     */
    public function waitForElementVisibleWithExtendedTimeout($element, $timeout=60)
    {
        $this->waitForElementVisible($element, $timeout);
    }

    /**
     * Method preparedFillField
     * @param string $field
     * @param string $value
     * @since 2.2.0
     */
    public function preparedFillField($field, $value)
    {
        $this->waitForElementVisibleWithExtendedTimeout($field);
        $this->fillField($field, $value);
    }
    /**
     * Method preparedClick
     * @param string $link
     * @param string $context
     * @since 2.2.0
     */
    public function preparedClick($link, $context = null)
    {
        $this->waitForElementVisibleWithExtendedTimeout($link);
        $this->waitForElementClickableWithExtendedTimeout($link);
        $this->click($link, $context);
    }

    /**
     * Method preparedClick
     * @param string $select
     * @param string $option
     * @since 2.2.0
     */
    public function preparedSelectOption($select, $option)
    {
        $this->waitForElementVisibleWithExtendedTimeout($select);
        $this->waitForElementClickableWithExtendedTimeout($select);
        $this->selectOption($select, $option);
    }
}
