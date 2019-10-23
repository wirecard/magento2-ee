<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Validator;

interface ValidatorInterface
{
    /**
     * Validation for business related object
     *
     * @param array $validationSubject
     * @return bool
     * @since 2.2.1
     */
    public function validate(array $validationSubject);
}
