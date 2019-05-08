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

namespace Page;

class Payment extends Base
{
    /**
     * @var string
     * @since 1.4.1
     */
    public $URL = 'index.php/checkout#payment';

    /**
     * @var array
     * @since 1.4.1
     */

    public $elements = [
        'Wirecard Credit Card' => "//*[@id='wirecard_elasticengine_creditcard']",
        'Place Order' => "//*[@id='wirecard_elasticengine_creditcard_submit']",
        'Credit Card First Name' => "//*[@id='first_name']",
        'Credit Card Last Name' => "//*[@id='last_name']",
        'Credit Card Card number' => "//*[@id='account_number']",
        'Credit Card CVV' => "//*[@id='card_security_code']",
        'Credit Card Valid until month' => "//*[@name='expiration_month']",
        'Credit Card Valid until year' => "//*[@name='expiration_year']"
    ];

    /**
     * Method fillCreditCardDetails
     * @since 1.4.1
     */
    public function fillCreditCardDetails()
    {
        $I = $this->tester;
        $I->wait(20);
        $data_field_values = $I->getDataFromDataFile('tests/_data/CardData.json');
        $I->selectOption($this->getElement('Wirecard Credit Card'), 'Wirecard Credit Card');
        $this->switchFrame();
        $I->waitForElementVisible($this->getElement('Credit Card Last Name'));
        $I->fillField($this->getElement('Credit Card Last Name'), $data_field_values->last_name);
        $I->fillField($this->getElement('Credit Card Card number'), $data_field_values->card_number);
        $I->fillField($this->getElement('Credit Card CVV'), $data_field_values->cvv);
        $I->selectOption($this->getElement('Credit Card Valid until month'), $data_field_values->valid_until_month);
        $I->selectOption($this->getElement('Credit Card Valid until year'), $data_field_values->valid_until_year);
        $I->switchToIFrame();
        $I->click($this->getElement('Place Order'));
    }

    /**
     * Method switchFrame
     * @since 1.4.1
     */
    public function switchFrame()
    {
        // Switch to Credit Card UI frame
        $I = $this->tester;
        //wait for Javascript to load iframe and it's contents
        $I->wait(2);
        //get wirecard seemless frame name
        $wirecard_frame_name = $I->executeJS('return document.querySelector(".wirecard-seamless-frame").getAttribute("name")');
        $I->switchToIFrame("$wirecard_frame_name");
    }
}
