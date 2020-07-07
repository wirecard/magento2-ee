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
use Wirecard\PaymentSdk\Entity\FormFieldMap;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;

/**
 * This class is for creating a Credit Card three d form
 *
 * Class CreditCardThreeDForm
 * @package Wirecard\ElasticEngine\Block\Checkout
 * @since 3.1.2
 */
class CreditCardThreeDForm extends Template
{
    /** @var FormInteractionResponse */
    private $response;

    /**
     * @param FormInteractionResponse $response
     * @since 3.1.2
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return string
     * @since 3.1.2
     */
    public function getMethod()
    {
        return $this->response->getMethod();
    }

    /**
     * @return string
     * @since 3.1.2
     */
    public function getAction()
    {
        return urldecode($this->response->getUrl());
    }

    /**
     * @return FormFieldMap
     * @since 3.1.2
     */
    public function getFormFields()
    {
        return $this->response->getFormFields();
    }
}
