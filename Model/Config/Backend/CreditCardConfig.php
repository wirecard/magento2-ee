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

namespace Wirecard\ElasticEngine\Model\Config\Backend;

use Magento\Framework\App\Config\Value;

class CreditCardConfig extends Value
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface $messageManager
     * @since 2.0.0
     */
    private $messageManager;

    /**
     * CreditCardConfig constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );

        $this->messageManager = $messageManager;
    }

    /**
     * @return Value|void
     * @since 2.0.0
     */
    public function beforeSave()
    {
        if (!$this->isUrlConfigurationValid()) {
            $this->messageManager->addWarningMessage(__('warning_credit_card_url_mismatch'));
        }

        parent::beforeSave();
    }

    /**
     * Scenarios checked in this method
     * base_url and wpp_url both contain "test"        = valid
     * base_url and wpp_url both do not contain "test" = valid
     * only base_url or wpp_url contains "test"        = invalid
     *
     * The information is used to check the possibility
     * of a mixed configuration (production and test)
     *
     * @return bool
     * @since 2.0.0
     */
    private function isUrlConfigurationValid()
    {
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
     * @param string $string
     * @param string $needle
     * @return bool
     * @since 2.0.0
     */
    private function stringContainsSubstring($string, $needle)
    {
        if (stripos($string, $needle) === false) {
            return false;
        }

        return true;
    }
}
