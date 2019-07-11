<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 *
 * @author Wirecard AG
 * @copyright Wirecard AG
 * @license GPLv3
 */

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
 */

use Page\Base;
use Page\Checkout as CheckoutPage;
use Page\OrderReceived as OrderReceivedPage;
use Page\Payment as PaymentPage;
use Page\Product3DS as Product3DSPage;
use Page\ProductNon3DS as ProductNon3DSPage;
use Page\Verified as VerifiedPage;

class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     * @var string
     * @since 1.4.1
     */
    private $currentPage;

    /**
     * Method selectPage
     *
     * @param string $name
     * @return Base
     *
     * @since   1.4.1
     */
    private function selectPage($name)
    {
        switch ($name) {
            case 'Checkout':
                $this->wait(5);
                $page = new CheckoutPage($this);
                break;
            case 'Product3DS':
                $page = new Product3DSPage($this);
                break;
            case 'ProductNon3DS':
                $page = new ProductNon3DSPage($this);
                break;
            case 'Verified':
                $this->wait(45);
                $page = new VerifiedPage($this);
                break;
            case 'Order Received':
                $this->wait(45);
                $page = new OrderReceivedPage($this);
                break;
            case 'Payment':
                $this->wait(45);
                $page = new PaymentPage($this);
                break;
            default:
                $page = null;
        }
        return $page;
    }

    /**
     * Method getPageElement
     *
     * @param string $elementName
     * @return string
     *
     * @since   1.4.1
     */
    private function getPageElement($elementName)
    {
        //Takes the required element by it's name from required page
        return $this->currentPage->getElement($elementName);
    }

    /**
     * @Given I am on :page page
     * @since 1.4.1
     */
    public function iAmOnPage($page)
    {
        // Open the page and initialize required pageObject
        $this->currentPage = $this->selectPage($page);
        $this->amOnPage($this->currentPage->getURL());
    }

    /**
     * @When I click :object
     * @since 1.4.1
     */
    public function iClick($object)
    {
        $this->waitForElementVisible($this->getPageElement($object));
        $this->waitForElementClickable($this->getPageElement($object));
        $this->click($this->getPageElement($object));
    }

    /**
     * @When I am redirected to :page page
     * @since 1.4.1
     */
    public function iAmRedirectedToPage($page)
    {
        // Initialize required pageObject WITHOUT checking URL
        $this->currentPage = $this->selectPage($page);
        // Check only specific keyword that page URL should contain
        $this->seeInCurrentUrl($this->currentPage->getPageSpecific());
    }

    /**
     * @When I fill fields with :data
     * @since 1.4.1
     */
    public function iFillFieldsWith($data)
    {
        $this->fillFieldsWithData($data, $this->currentPage);
    }

    /**
     * @When I enter :fieldValue in field :fieldID
     * @since 1.4.1
     */
    public function iEnterInField($fieldValue, $fieldID)
    {
        $this->waitForElementVisible($this->getPageElement($fieldID));
        $this->fillField($this->getPageElement($fieldID), $fieldValue);
    }

    /**
     * @Then I see :text
     * @since 1.4.1
     */
    public function iSee($text)
    {
        $this->see($text);
    }

    /**
     * @Given I prepare checkout :type
     * @since 1.4.1
     */
    public function iPrepareCheckout($type)
    {
        $page = 'Product3DS';
        if (strpos($type, 'Non3DS') !== false) {
            $page = 'ProductNon3DS';
        }
        $this->iAmOnPage($page);
        $this->wait(20);
        $this->click($this->currentPage->getElement('Add to Cart'));
        //this avoids problem of Magento giving 404 after CC non 3DS payment
        if (strpos($type, 'Non3DS') !== false) {
            $this->wait(10);
            $this->click($this->currentPage->getElement('Basket'));
            $this->wait(10);
            $this->click($this->currentPage->getElement('Proceed to Checkout'));
        }
    }

    /**
     * @When I check :box
     * @since 1.4.1
     */
    public function iCheck($box)
    {
        $this->currentPage->checkBox($box);
    }
}
