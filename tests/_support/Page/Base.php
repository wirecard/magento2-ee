<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Page;

class Base
{

    /**
     * @var string
     * @since 1.4.1
     */
    protected $URL = '';

    /**
     * @var array
     * @since 1.4.1
     */
    protected $elements = [];

    /**
     * @var string
     * @since 2.2.0
     */
    public $pageSpecific = '';

    /**
     * @var string
     * @since 1.4.1
     */
    protected $tester;

    /**
     * @var \AcceptanceTester
     * @since 1.4.1
     */
    public function __construct(\AcceptanceTester $I)
    {
        $this->tester = $I;
    }

    /**
     * Method getElement
     *
     * @param string $name
     * @return string
     *
     * @since 1.4.1
     */
    public function getElement($name)
    {
        return $this->elements[$name];
    }

    /**
     * Method getURL
     *
     * @return string
     *
     * @since 1.4.1
     */
    public function getURL()
    {
        return $this->URL;
    }

    /**
     * Method getPageSpecific
     *
     * @return string
     *
     * @since 1.5.3
     */
    public function getPageSpecific()
    {
        return $this->pageSpecific;
    }

    /**
     * Method fillBillingDetails
     *
     * @since 1.4.1
     */
    public function fillBillingDetails()
    {
    }

    /**
     * Method fillCreditCardDetails
     *
     * @since 1.4.1
     */
    public function fillCreditCardDetails()
    {
    }

    /**
     * Method checkBox
     * @param string $box
     * @since 1.4.1
     */
    public function checkBox($box)
    {
        $this->tester->checkOption($this->getElement($box));
    }

    /**
     * Method prepareCheckout
     *
     * @since 1.5.3
     */
    public function prepareCheckout()
    {
    }
}
