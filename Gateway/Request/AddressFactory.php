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
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Wirecard\PaymentSdk\Entity\Address;

/**
 * Class AddressFactory
 * @package Wirecard\ElasticEngine\Gateway\Request
 */
class AddressFactory
{
    /**
     * @param AddressAdapterInterface|QuoteAddress $magentoAddressObj
     * @return Address
     * @throws \InvalidArgumentException
     */
    public function create($magentoAddressObj)
    {
        $address = null;
        if (!$this->isValidAddressObject($magentoAddressObj)) {
            throw new \InvalidArgumentException('Address data object should be provided.');
        }

        $address = new Address(
            $magentoAddressObj->getCountryId(),
            $magentoAddressObj->getCity(),
            $this->getStreetLine1($magentoAddressObj)
        );
        $address->setPostalCode($magentoAddressObj->getPostcode());

        if (strlen($magentoAddressObj->getRegionCode())) {
            $address->setState($magentoAddressObj->getRegionCode());
        }

        if (strlen($this->getStreetLine2($magentoAddressObj))) {
            $address->setStreet2($this->getStreetLine2($magentoAddressObj));
        }

        return $address;
    }

    /**
     * @param AddressAdapterInterface|QuoteAddress $magentoAddressObj
     * @return string
     * @since 3.0.0
     */
    private function getStreetLine1($magentoAddressObj)
    {
        if ($magentoAddressObj instanceof AddressAdapterInterface) {
            /** AddressAdapterInterface $magentoAddressObj */
            return $magentoAddressObj->getStreetLine1();
        }
        /** QuoteAddress $magentoAddressObj */
        return $magentoAddressObj->getStreetLine(1);
    }

    /**
     * @param AddressAdapterInterface|QuoteAddress $magentoAddressObj
     * @return string
     * @since 3.0.0
     */
    private function getStreetLine2($magentoAddressObj)
    {
        if ($magentoAddressObj instanceof AddressAdapterInterface) {
            /** AddressAdapterInterface $magentoObj */
            return $magentoAddressObj->getStreetLine2();
        }
        /** QuoteAddress $magentoObj */
        return $magentoAddressObj->getStreetLine(2);
    }

    /**
     * @param AddressAdapterInterface|QuoteAddress $magentoAddressObj
     * @return bool
     * @since 3.0.0
     */
    private function isValidAddressObject($magentoAddressObj)
    {
        if (!$magentoAddressObj instanceof AddressAdapterInterface && !$magentoAddressObj instanceof QuoteAddress) {
            return false;
        }
        return true;
    }
}
