<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
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
        // Removal of deprecated class Registry not yet possible - check again with Magento 2.4
        // and clarify breaking compatibility
        parent::__construct(
            $context,
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
