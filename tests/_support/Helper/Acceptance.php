<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Lib\Generator\PageObject;

class Acceptance extends \Codeception\Module
{

    /**
     * Method getDataFromDataFile
     * @param string $fileName
     * @return string
     *
     * @since 1.4.1
     */
    public static function getDataFromDataFile($fileName)
    {
        // decode the JSON feed
        $json_data = json_decode(file_get_contents($fileName));
        if (! $json_data) {
            $error = error_get_last();
            echo 'Failed to get customer data from tests/_data/CustomerData.json. Error was: ' . $error['message'];
        } else {
            return $json_data;
        }
    }

    /**
     * Method fillFieldsWithData
     *
     * @param string $dataType
     * @param PageObject $page
     *
     * @since 1.4.1
     */
    public static function fillFieldsWithData($dataType, $page)
    {
        if (strpos($dataType, 'Customer') !== false) {
            $page->fillBillingDetails();
        } elseif (strpos($dataType, 'Credit Card') !== false) {
            $page->fillCreditCardDetails();
        }
    }
}
