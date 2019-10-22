<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Request;

use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Wirecard\PaymentSdk\Entity\Address;

/**
 * Class AddressFactory
 * @package Wirecard\ElasticEngine\Gateway\Request
 */
class AddressFactory
{
    /**
     * @param AddressAdapterInterface $magentoAddressObj
     * @return Address
     * @throws \InvalidArgumentException
     */
    public function create($magentoAddressObj)
    {
        $address = null;
        if (!$magentoAddressObj instanceof AddressAdapterInterface) {
            throw new \InvalidArgumentException('Address data object should be provided.');
        }

        if (self::isValidAddress($magentoAddressObj)) {
            $address = new Address(
                $magentoAddressObj->getCountryId(),
                $magentoAddressObj->getCity(),
                $magentoAddressObj->getStreetLine1()
            );
            $address->setPostalCode($magentoAddressObj->getPostcode());

            if (strlen($magentoAddressObj->getRegionCode())) {
                $address->setState($magentoAddressObj->getRegionCode());
            }

            if (strlen($magentoAddressObj->getStreetLine2())) {
                $address->setStreet2($magentoAddressObj->getStreetLine2());
            }
        }

        return $address;
    }

    /**
     * Validation of mandatory address fields
     *
     * @param AddressAdapterInterface | \Magento\Quote\Api\Data\AddressInterface $magentoAddressObj
     * @return bool
     * @since 2.2.1
     */
    public static function isValidAddress($magentoAddressObj)
    {
        $valid = true;
        if (empty($magentoAddressObj->getCountryId())
            || empty($magentoAddressObj->getCity())
            || empty($magentoAddressObj->getStreetLine1())
        ) {
            $valid = false;
        }

        return $valid;
    }
}
