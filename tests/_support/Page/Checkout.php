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

class Checkout extends Base
{

    /**
     * @var string
     * @since 1.4.1
     */
    public $URL = 'index.php/checkout#shipping';
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
