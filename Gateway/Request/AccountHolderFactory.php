<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
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
     * @param string $customerBirthdate
     * @param string $firstName
     * @param string $lastName
     * @return AccountHolder
     * @throws \InvalidArgumentException
     */
    public function create($magentoAddressObj, $customerBirthdate = null, $firstName = null, $lastName = null)
    {
        if (!$magentoAddressObj instanceof AddressAdapterInterface) {
            throw new \InvalidArgumentException('Address data object should be provided.');
        }

        $accountHolder = new AccountHolder();
        $accountHolder->setAddress($this->addressFactory->create($magentoAddressObj));
        $accountHolder->setEmail($magentoAddressObj->getEmail());

        if ($firstName != null) {
            $accountHolder->setFirstName($firstName);
        } elseif ($firstName == null && $lastName == null) {
            // The else-if here exists primarily so we don't use the billing name
            // if the seamless first name was left empty.
            $accountHolder->setFirstName($magentoAddressObj->getFirstname());
        }

        if ($lastName != null) {
            $accountHolder->setLastName($lastName);
        } else {
            // No need for an else-if here because if the last name is required by
            // our seamless form.
            $accountHolder->setLastName($magentoAddressObj->getLastname());
        }

        $accountHolder->setPhone($magentoAddressObj->getTelephone());

        if ($customerBirthdate != null) {
            $accountHolder->setDateOfBirth(new \DateTime($customerBirthdate));
        }

        return $accountHolder;
    }
}
