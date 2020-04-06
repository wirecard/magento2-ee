<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Helper;

use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Address;

/**
 * Class AccountHolderMapper
 * @package Wirecard\ElasticEngine\Gateway\Helper
 * @since 3.1.2
 */
class AccountHolderMapper
{
    const KEY_COUNTRY_ID = 'countryId';
    const KEY_CITY = 'city';
    const KEY_STREET = 'street';
    const KEY_REGION_CODE = 'regionCode';
    const KEY_POST_CODE = 'postcode';
    const KEY_FIRST_NAME = 'firstname';
    const KEY_LAST_NAME = 'lastname';
    const KEY_PHONE = 'telephone';

    /** @var AccountHolder */
    private $accountHolder;

    /** @var mixed */
    private $accountHolderData;

    /**
     * AccountHolderMapper constructor.
     * @param AccountHolder $accountHolder
     * @param $accountHolderData
     * @since 3.1.2
     */
    public function __construct(AccountHolder $accountHolder, $accountHolderData)
    {
        $this->accountHolder = $accountHolder;
        $this->accountHolderData = json_decode($accountHolderData, false);
    }

    /**
     * Update account holder personal data
     * @return AccountHolder
     * @since 3.1.2
     */
    public function updateAccountHolder()
    {
        $this->updateAccountHolderAddress();
        if (property_exists($this->accountHolderData, self::KEY_FIRST_NAME)) {
            $this->accountHolder->setFirstName($this->accountHolderData->firstname);
        }
        if (property_exists($this->accountHolderData, self::KEY_LAST_NAME)) {
            $this->accountHolder->setLastName($this->accountHolderData->lastname);
        }
        if (property_exists($this->accountHolderData, self::KEY_PHONE)) {
            $this->accountHolder->setPhone($this->accountHolderData->telephone);
        }

        return $this->accountHolder;
    }

    /**
     * Update account holder address data
     * @since 3.1.2
     */
    private function updateAccountHolderAddress()
    {
        if (property_exists($this->accountHolderData, self::KEY_COUNTRY_ID) &&
            property_exists($this->accountHolderData, self::KEY_CITY) &&
            property_exists($this->accountHolderData, self::KEY_STREET) &&
            is_array($this->accountHolderData->street) &&
            count($this->accountHolderData->street) > 0
        ) {
            $address = new Address(
                $this->accountHolderData->countryId,
                $this->accountHolderData->city,
                $this->accountHolderData->street[0]
            );
            $streets = $this->accountHolderData->street;
            if (array_key_exists(1, $streets)) {
                $address->setStreet2($this->accountHolderData->street[1]);
            }
            if (array_key_exists(2, $streets)) {
                $address->setStreet3($this->accountHolderData->street[2]);
            }
            if (property_exists($this->accountHolderData, self::KEY_REGION_CODE)) {
                $address->setState($this->accountHolderData->region);
            }
            if (property_exists($this->accountHolderData, self::KEY_POST_CODE)) {
                $address->setPostalCode($this->accountHolderData->postcode);
            }
            $this->accountHolder->setAddress($address);
        }
    }
}
