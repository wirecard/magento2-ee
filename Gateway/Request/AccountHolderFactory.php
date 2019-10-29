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
use Wirecard\ElasticEngine\Gateway\Validator;
use Wirecard\ElasticEngine\Gateway\Validator\ValidatorFactory;
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
     * @var ValidatorFactory
     */
    private $validatorFactory;

    /**
     * AccountHolderFactory constructor.
     * @param AddressFactory $addressFactory
     * @param ValidatorFactory $validatorFactory
     */
    public function __construct(AddressFactory $addressFactory, ValidatorFactory $validatorFactory)
    {
        $this->addressFactory = $addressFactory;
        $this->validatorFactory = $validatorFactory;
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
        $addressInterfaceValidator = $this->validatorFactory->create(Validator::ADDRESS_ADAPTER_INTERFACE, $magentoAddressObj);
        if ($addressInterfaceValidator->validate()) {
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
