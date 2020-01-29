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
use Wirecard\ElasticEngine\Gateway\Validator\AddressValidatorFactory;
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
     * @var AddressValidatorFactory
     */
    private $validatorFactory;

    /**
     * AccountHolderFactory constructor.
     * @param AddressFactory $addressFactory
     * @param AddressValidatorFactory $addressValidatorFactory
     */
    public function __construct(AddressFactory $addressFactory, AddressValidatorFactory $addressValidatorFactory)
    {
        $this->addressFactory = $addressFactory;
        $this->validatorFactory = $addressValidatorFactory;
    }

    /**
     * @param AddressAdapterInterface|QuoteAddress $magentoAddressObj
     * @param string|null $customerBirthdate
     * @param string|null $firstName
     * @param string|null $lastName
     * @return AccountHolder
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function create($magentoAddressObj, $customerBirthdate = null, $firstName = null, $lastName = null)
    {
        $validator = $this->validatorFactory->create($magentoAddressObj);
        $accountHolder = new AccountHolder();
        $accountHolder = $this->getAccountHolderWithNames($firstName, $lastName, $magentoAddressObj, $accountHolder);
        if ($validator->validate()) {
            $accountHolder->setAddress($this->addressFactory->create($magentoAddressObj));
        }
        $accountHolder->setEmail($magentoAddressObj->getEmail());
        $accountHolder->setPhone($magentoAddressObj->getTelephone());

        if ($customerBirthdate !== null) {
            $accountHolder->setDateOfBirth(new \DateTime($customerBirthdate));
        }

        return $accountHolder;
    }

    /**
     * @param string|null $firstname
     * @param string|null $lastname
     * @param AddressAdapterInterface|QuoteAddress $magentoAddressObj
     * @param AccountHolder $accountHolder
     * @return AccountHolder
     * @since 3.0.0
     */
    private function getAccountHolderWithNames($firstname, $lastname, $magentoAddressObj, $accountHolder)
    {
        if (empty($lastname)) {
            $accountHolder->setFirstname($magentoAddressObj->getFirstname());
            $accountHolder->setLastname($magentoAddressObj->getLastname());
            return $accountHolder;
        }
        $accountHolder->setLastname($lastname);
        if (!empty($firstname)) {
            $accountHolder->setFirstname($firstname);
        }
        return $accountHolder;
    }
}
