<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Validator;

use Magento\Quote\Model\Quote\Address;

/**
 * Class QuoteAddressValidator
 * @package Wirecard\ElasticEngine\Gateway\Validator
 */
class QuoteAddressValidator extends AbstractValidator
{
    /**
     * Validation for business related object
     *
     * @param array $validationSubject
     * @return bool
     * @since 2.2.1
     */
    public function validate(array $validationSubject)
    {
        $isValid = true;

        /** @var Address $addressObj */
        $addressObj = $validationSubject['addressObject'];
        if (empty($addressObj->getCountryId())
            || empty($addressObj->getCity())
            || empty($addressObj->getStreetLine(1))
        ) {
            $isValid = false;
        }

        return $isValid;
    }
}
