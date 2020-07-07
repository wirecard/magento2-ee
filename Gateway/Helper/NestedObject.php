<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Helper;

/**
 * Helper for managing nested objects
 *
 * @since 2.1.0
 */
class NestedObject
{
    /**
     * in deep property get for nested objects
     *
     * @param object $object
     * @param string[] $path
     *
     * @return null|mixed
     */
    public function getIn($object, $path)
    {
        $elem = $object;
        foreach ($path as $prop) {
            $elem = $this->get($elem, $prop);
            if (!is_object($elem)) {
                return $elem;
            }
        }

        return $elem;
    }

    /**
     * safe property geter
     *
     * @param object $object
     * @param string $property
     *
     * @return null|mixed
     */
    public function get($object, $property)
    {
        if (!is_object($object)) {
            return null;
        }

        if (!property_exists($object, $property)) {
            return null;
        }

        return $object->$property;
    }
}
