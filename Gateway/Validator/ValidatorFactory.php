<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Validator;

/**
 * Class ValidatorFactory
 * @package Wirecard\ElasticEngine\Gateway\Validator
 * @since 2.2.1
 */
class ValidatorFactory
{
    const VALIDATOR_NAMESPACE = "\\Wirecard\\ElasticEngine\\Gateway\\Validator\\";

    /**
     * Create validator with specific type
     *
     * @param string $type
     * @param mixed $object
     * @return AbstractValidator
     * @throws \InvalidArgumentException
     */
    public function create($type, $object)
    {
        $class = self::VALIDATOR_NAMESPACE . $type;

        if (class_exists($class)) {
            return new $class($object);
        }
        throw new \InvalidArgumentException('Invalid validator given');
    }
}
