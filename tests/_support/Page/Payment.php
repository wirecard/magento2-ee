<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Page;

class Payment extends Base
{
    /**
     * @var string
     * @since 1.4.1
     */
    public $URL = 'payment';

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
