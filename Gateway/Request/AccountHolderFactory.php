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
use Wirecard\PaymentSdk\Entity\AccountHolder;

/**
 * Class AccountHolderFactory
 * @package Wirecard\ElasticEngine\Gateway\Request
 */
class AccountHolderFactory
{
    /**
     * @var AddressFactory
     */
    private $addressFactory;

    /**
     * AccountHolderFactory constructor.
     * @param AddressFactory $addressFactory
     */
    public function __construct(AddressFactory $addressFactory)
    {
        $this->addressFactory = $addressFactory;
    }

    /**
     * @param AddressAdapterInterface $magentoAddressObj
     * @param string|null $customerBirthdate
     * @param string|null $firstName
     * @param string|null $lastName
     * @return AccountHolder
     * @throws \InvalidArgumentException
     */
    public function create($magentoAddressObj, $customerBirthdate = null, $firstName = null, $lastName = null)
    {
        if (!$magentoAddressObj instanceof AddressAdapterInterface) {
            throw new \InvalidArgumentException('Address data object should be provided.');
        }

        $accountHolder = new AccountHolder();
        $address = $this->addressFactory->create($magentoAddressObj);
        if ($address !== null) {
            $accountHolder->setAddress($this->addressFactory->create($magentoAddressObj));
        }
        $accountHolder->setEmail($magentoAddressObj->getEmail());

        // This is a special case for credit card
        // If we get a last name (and maybe first name) from the seamless form, that is our actual account holder.
        if ($lastName !== null) {
            $accountHolder->setLastName($lastName);

            if ($firstName !== null) {
                $accountHolder->setFirstName($firstName);
            }
        } else {
            $accountHolder->setFirstName($magentoAddressObj->getFirstname());
            $accountHolder->setLastName($magentoAddressObj->getLastname());
        }

        $accountHolder->setPhone($magentoAddressObj->getTelephone());

        if ($customerBirthdate !== null) {
            $accountHolder->setDateOfBirth(new \DateTime($customerBirthdate));
        }

        return $accountHolder;
    }
}
