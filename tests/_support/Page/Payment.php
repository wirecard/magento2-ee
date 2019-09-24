<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Page;

use Facebook\WebDriver\Exception\UnknownServerException;

class Payment extends Base
{
    /**
     * @var string
     * @since 1.4.1
     */
    public $URL = 'payment';

    /**
     * @var string
     * @since 2.2.0
     */
    public $pageSpecific = 'payment';

    /**
     * @var array
     * @since 1.4.1
     */

    public $elements = [
        'Wirecard Credit Card' => "//*[@id='wirecard_elasticengine_creditcard']",
        'Place Order' => "//*[@id='wirecard_elasticengine_creditcard_submit']",
        'Credit Card First Name' => "//*[@id='pp-cc-first-name']",
        'Credit Card Last Name' => "//*[@id='pp-cc-last-name']",
        'Credit Card Card number' => "//*[@id='pp-cc-account-number']",
        'Credit Card CVV' => "//*[@id='pp-cc-cvv']",
        'Credit Card Valid until month / year' => "//*[@id='pp-cc-expiration-date']"
    ];

    /**
     * Method fillCreditCardDetails
     * @since 1.4.1
     */
    public function fillCreditCardDetails()
    {
        $I = $this->tester;
        $data_field_values = $I->getDataFromDataFile('tests/_data/CardData.json');
        $I->wait(5);
        $I->preparedSelectOption($this->getElement('Wirecard Credit Card'), 'Wirecard Credit Card');
        $I->wait(15);
        try {
            $this->switchFrame();
        } catch (UnknownServerException $e) {
            $I->reloadPage();
            $I->wait(15);
            $this->switchFrame();
        }
        $I->preparedFillField($this->getElement('Credit Card Last Name'), $data_field_values->last_name);
        $I->preparedFillField($this->getElement('Credit Card Card number'), $data_field_values->card_number);
        $I->preparedFillField($this->getElement('Credit Card CVV'), $data_field_values->cvv);
        $I->preparedFillField($this->getElement('Credit Card Valid until month / year'), $data_field_values->expiration_date);
        $I->switchToIFrame();
        $I->preparedClick($this->getElement('Place Order'));
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
        $wirecard_frame_name = $I->executeJS('return document.querySelector("#wirecard-integrated-payment-page-frame").getAttribute("name")');
        $I->switchToIFrame("$wirecard_frame_name");
    }
}
