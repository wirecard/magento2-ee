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

    public $pageSpecific = 'checkout';

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
        $I->preparedSelectOption($this->getElement('Country'), $data_field_values->country);
        $I->preparedSelectOption($this->getElement('State/Province'), $data_field_values->state);
        $I->preparedFillField($this->getElement('Email Address'), $data_field_values->email_address);
        $I->preparedFillField($this->getElement('First Name'), $data_field_values->first_name);
        $I->preparedFillField($this->getElement('Last Name'), $data_field_values->last_name);
        $I->preparedFillField($this->getElement('Street Address'), $data_field_values->street_address);
        $I->preparedFillField($this->getElement('City'), $data_field_values->town);
        $I->preparedFillField($this->getElement('Zip/Postal Code'), $data_field_values->post_code);
        $I->preparedFillField($this->getElement('Phone Number'), $data_field_values->phone);
        $I->wait(10);
        $I->preparedClick($this->getElement('Next'));
    }
}
