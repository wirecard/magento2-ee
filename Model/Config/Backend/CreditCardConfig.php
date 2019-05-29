<?php
/**
 * Created by IntelliJ IDEA.
 * User: sebastian.russmann
 * Date: 5/29/2019
 * Time: 10:03 AM
 */

namespace Wirecard\ElasticEngine\Model\Config\Backend;

use Magento\Framework\App\Config\Value;

class CreditCardConfig extends Value
{
    const ERR_MSG_MIXED_CREDENTIALS = "Attention: Please check your credentials within the URL setting fields. You might have configured/combined a productive account with a test account.";

    public function beforeSave()
    {
        if (!$this->isUrlConfigurationValid()) {
            throw new \Magento\Framework\Exception\ValidatorException(__(self::ERR_MSG_MIXED_CREDENTIALS));
        }

        parent::beforeSave();
    }

    /**
     * @return bool
     */
    private function isUrlConfigurationValid() {
        $baseUrl = (string)$this->getFieldsetDataValue('base_url');
        $wppUrl  = (string)$this->getFieldsetDataValue('wpp_url');
        $needle  = 'test';

        $baseUrlContainsTest = $this->stringContainsSubstring($baseUrl, $needle);
        $wppUrlContainsTest  = $this->stringContainsSubstring($wppUrl, $needle);

        if ($baseUrlContainsTest === $wppUrlContainsTest) {
            return true;
        }

        return false;
    }

    /**
     * @param $string
     * @param $needle
     * @return bool
     */
    private function stringContainsSubstring($string, $needle) {
        if (stripos($string, $needle) === false) {
            return false;
        }

        return true;
    }

}