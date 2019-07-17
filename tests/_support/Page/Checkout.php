<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Page;

class Checkout extends Base
{

    /**
     * @var string
     * @since 1.4.1
     */
    public $URL = 'index.php/checkout#shipping';
    /**
     * @var string
     * @since 1.5.3
     */
    public $page_specific = 'checkout';
    /**
     * @var array
     * @since 1.4.1
     */

    public $elements = [
        'First Name' => "//*[@name='firstname']",
        'Last Name' => "//*[@name='lastname']",
        'Email Address' => "//*[@id='customer-email']",
        'Street Address' => "//*[@name='street[0]']",
        'City' => "//*[@name='city']",
        'Zip/Postal Code' => "//*[@name='postcode']",
        'Phone Number' => "//*[@name='telephone']",
        'Country' => "//*[@name='country_id']",
        'State/Province' => "//*[@name='region_id']",
        'Next' => "//*[@class='button action continue primary']"
    ];

    /**
     * Method fillBillingDetails
     *
     * @since 1.4.1
     */
    public function fillBillingDetails()
    {
        $I = $this->tester;
        $data_field_values = $I->getDataFromDataFile('tests/_data/CustomerData.json');
        $I->wait(45);
        $I->waitForElementVisible($this->getElement('Country'));
        $I->selectOption($this->getElement('Country'), $data_field_values->country);
        $I->waitForElementVisible($this->getElement('State/Province'));
        $I->selectOption($this->getElement('State/Province'), $data_field_values->state);
        $I->waitForElementVisible($this->getElement('Email Address'));
        $I->fillField($this->getElement('Email Address'), $data_field_values->email_address);
        $I->waitForElementVisible($this->getElement('First Name'));
        $I->fillField($this->getElement('First Name'), $data_field_values->first_name);
        $I->waitForElementVisible($this->getElement('Last Name'));
        $I->fillField($this->getElement('Last Name'), $data_field_values->last_name);
        $I->waitForElementVisible($this->getElement('Street Address'));
        $I->fillField($this->getElement('Street Address'), $data_field_values->street_address);
        $I->waitForElementVisible($this->getElement('City'));
        $I->fillField($this->getElement('City'), $data_field_values->town);
        $I->waitForElementVisible($this->getElement('Zip/Postal Code'));
        $I->fillField($this->getElement('Zip/Postal Code'), $data_field_values->post_code);
        $I->waitForElementVisible($this->getElement('Phone Number'));
        $I->fillField($this->getElement('Phone Number'), $data_field_values->phone);
        $I->waitForElementVisible($this->getElement('Next'));
        $I->wait(4);
        $I->click($this->getElement('Next'));
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
        return $this->page_specific;
    }
}
