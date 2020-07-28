<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Block\Checkout;

use Magento\Framework\View\Element\Template;

/**
 *  Form fields to create a form that is later posted in frontend
 *
 * @since 3.1.5
 */
class FormFields extends Template
{
    /** @var string */
    private $method;

    /** @var string */
    private $action;

    /** @var array */
    private $formFields;

    /**
     * @param string $method
     * @since 3.1.5
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @param string $action
     * @since 3.1.5
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @param array $formFields
     * @since 3.1.5
     */
    public function setFormFields($formFields)
    {
        $this->formFields = $formFields;
    }

    /**
     * @return string
     * @since 3.1.2
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     * @since 3.1.2
     */
    public function getAction()
    {
        return urldecode($this->action);
    }

    /**
     * @return array
     * @since 3.1.5
     */
    public function getFormFields()
    {
        return $this->formFields;
    }
}
