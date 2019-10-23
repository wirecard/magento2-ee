<?php

namespace Wirecard\ElasticEngine\Gateway\Validator;

use Magento\Quote\Model\Quote\Address;

class AddressValidator extends AbstractValidator
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
