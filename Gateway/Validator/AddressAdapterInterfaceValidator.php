<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Validator;

use Magento\Payment\Gateway\Data\AddressAdapterInterface;

/**
 * Class AddressInterfaceValidator
 * @package Wirecard\ElasticEngine\Gateway\Validator
 * @since 2.2.1
 */
class AddressAdapterInterfaceValidator extends AbstractValidator
{
    /**
     * @var AddressAdapterInterface
     */
    private $addressAdapter;

    /**
     * AddressAdapterInterfaceValidator constructor.
     * @param AddressAdapterInterface $addressAdapter
     * @since 2.2.1
     */
    public function __construct(AddressAdapterInterface $addressAdapter)
    {
        $this->addressAdapter = $addressAdapter;
    }

    /**
     * Validation for business related object
     *
     * @param array $validationParams
     * @return bool
     * @since 2.2.1
     */
    public function validate(array $validationParams = [])
    {
        $isValid = true;
        if (empty($this->addressAdapter->getCountryId())
            || empty($this->addressAdapter->getCity())
            || empty($this->addressAdapter->getStreetLine1())
        ) {
            $isValid = false;
        }

        return $isValid;
    }
}
