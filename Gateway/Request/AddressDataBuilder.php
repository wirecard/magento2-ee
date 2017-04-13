<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard Central Eastern Europe GmbH
 * (abbreviated to Wirecard CEE) and are explicitly not part of the Wirecard CEE range of
 * products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard CEE does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard CEE does not guarantee their full
 * functionality neither does Wirecard CEE assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard CEE does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

namespace Wirecard\ElasticEngine\Gateway\Request;

use Magento\Braintree\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class AddressDataBuilder
 */
class AddressDataBuilder implements BuilderInterface
{
    /**
     * ShippingAddress block name
     */
    const SHIPPING_ADDRESS = 'shipping';

    /**
     * BillingAddress block name
     */
    const BILLING_ADDRESS = 'billing';

    /**
     * The first name value must be less than or equal to 255 characters.
     */
    const FIRST_NAME = 'firstName';

    /**
     * The last name value must be less than or equal to 255 characters.
     */
    const LAST_NAME = 'lastName';

    /**
     * The customer’s company. 255 character maximum.
     */
    const COMPANY = 'company';

    /**
     * The street address. Maximum 255 characters, and must contain at least 1 digit.
     * Required when AVS rules are configured to require street address.
     */
    const STREET_ADDRESS = 'streetAddress';

    /**
     * The extended address information—such as apartment or suite number. 255 character maximum.
     */
    const EXTENDED_ADDRESS = 'extendedAddress';

    /**
     * The locality/city. 255 character maximum.
     */
    const LOCALITY = 'locality';

    /**
     * The state or province. For PayPal addresses, the region must be a 2-letter abbreviation;
     * for all other payment methods, it must be less than or equal to 255 characters.
     */
    const REGION = 'region';

    /**
     * The postal code. Postal code must be a string of 5 or 9 alphanumeric digits,
     * optionally separated by a dash or a space. Spaces, hyphens,
     * and all other special characters are ignored.
     */
    const POSTAL_CODE = 'postalCode';

    /**
     * The ISO 3166-1 alpha-2 country code specified in an address.
     * The gateway only accepts specific alpha-2 values.
     *
     * @link https://developers.braintreepayments.com/reference/general/countries/php#list-of-countries
     */
    const COUNTRY_CODE = 'countryCodeAlpha2';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * Constructor
     *
     * @param SubjectReader $subjectReader
     */
    public function __construct(SubjectReader $subjectReader)
    {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDO->getOrder();
        $result = [];

        $billingAddress = $order->getBillingAddress();
        if ($billingAddress) {
            $result[self::BILLING_ADDRESS] = [
                self::FIRST_NAME => $billingAddress->getFirstname(),
                self::LAST_NAME => $billingAddress->getLastname(),
                self::COMPANY => $billingAddress->getCompany(),
                self::STREET_ADDRESS => $billingAddress->getStreetLine1(),
                self::EXTENDED_ADDRESS => $billingAddress->getStreetLine2(),
                self::LOCALITY => $billingAddress->getCity(),
                self::REGION => $billingAddress->getRegionCode(),
                self::POSTAL_CODE => $billingAddress->getPostcode(),
                self::COUNTRY_CODE => $billingAddress->getCountryId()
            ];
        }

        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $result[self::SHIPPING_ADDRESS] = [
                self::FIRST_NAME => $shippingAddress->getFirstname(),
                self::LAST_NAME => $shippingAddress->getLastname(),
                self::COMPANY => $shippingAddress->getCompany(),
                self::STREET_ADDRESS => $shippingAddress->getStreetLine1(),
                self::EXTENDED_ADDRESS => $shippingAddress->getStreetLine2(),
                self::LOCALITY => $shippingAddress->getCity(),
                self::REGION => $shippingAddress->getRegionCode(),
                self::POSTAL_CODE => $shippingAddress->getPostcode(),
                self::COUNTRY_CODE => $shippingAddress->getCountryId()
            ];
        }

        return $result;
    }
}
