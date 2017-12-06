<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
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

namespace Wirecard\ElasticEngine\Model\Adminhtml;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\DataObject;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Module\ModuleList\Loader;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Payment\Model\Config;

class Support
{
    /**
     * @var TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Framework\Module\ModuleList\Loader
     */
    protected $_moduleLoader;

    /**
     * @var \Magento\Payment\Model\Config
     */
    protected $_paymentConfig;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var array
     */
    protected $_moduleBlacklist = [
        'Magento_Store',
        'Magento_AdvancedPricingImportExport',
        'Magento_Directory',
        'Magento_Theme',
        'Magento_Backend',
        'Magento_Backup',
        'Magento_Eav',
        'Magento_Customer',
        'Magento_BundleImportExport',
        'Magento_AdminNotification',
        'Magento_CacheInvalidate',
        'Magento_Indexer',
        'Magento_Cms',
        'Magento_CatalogImportExport',
        'Magento_Catalog',
        'Magento_Rule',
        'Magento_Msrp',
        'Magento_Search',
        'Magento_Bundle',
        'Magento_Quote',
        'Magento_CatalogUrlRewrite',
        'Magento_Widget',
        'Magento_SalesSequence',
        'Magento_CheckoutAgreements',
        'Magento_Payment',
        'Magento_Downloadable',
        'Magento_CmsUrlRewrite',
        'Magento_Config',
        'Magento_ConfigurableImportExport',
        'Magento_CatalogInventory',
        'Magento_SampleData',
        'Magento_Contact',
        'Magento_Cookie',
        'Magento_Cron',
        'Magento_CurrencySymbol',
        'Magento_CatalogSearch',
        'Magento_CustomerImportExport',
        'Magento_CustomerSampleData',
        'Magento_Deploy',
        'Magento_Developer',
        'Magento_Dhl',
        'Magento_Authorization',
        'Magento_User',
        'Magento_ImportExport',
        'Magento_Sales',
        'Magento_CatalogRule',
        'Magento_Email',
        'Magento_EncryptionKey',
        'Magento_Fedex',
        'Magento_GiftMessage',
        'Magento_Checkout',
        'Magento_GoogleAnalytics',
        'Magento_GoogleOptimizer',
        'Magento_GroupedImportExport',
        'Magento_GroupedProduct',
        'Magento_Tax',
        'Magento_DownloadableImportExport',
        'Magento_Integration',
        'Magento_LayeredNavigation',
        'Magento_Marketplace',
        'Magento_MediaStorage',
        'Magento_ConfigurableProduct',
        'Magento_MsrpSampleData',
        'Magento_Multishipping',
        'Magento_NewRelicReporting',
        'Magento_Newsletter',
        'Magento_OfflinePayments',
        'Magento_SalesRule',
        'Magento_OfflineShipping',
        'Magento_PageCache',
        'Magento_Captcha',
        'Magento_Persistent',
        'Magento_ProductAlert',
        'Magento_Weee',
        'Magento_ProductVideo',
        'Magento_CatalogSampleData',
        'Magento_Reports',
        'Magento_RequireJs',
        'Magento_Review',
        'Magento_BundleSampleData',
        'Magento_Rss',
        'Magento_DownloadableSampleData',
        'Magento_OfflineShippingSampleData',
        'Magento_ConfigurableSampleData',
        'Magento_SalesSampleData',
        'Magento_ProductLinksSampleData',
        'Magento_ThemeSampleData',
        'Magento_ReviewSampleData',
        'Magento_SendFriend',
        'Magento_Ui',
        'Magento_Sitemap',
        'Magento_CatalogRuleConfigurable',
        'Magento_Swagger',
        'Magento_Swatches',
        'Magento_SwatchesSampleData',
        'Magento_GroupedProductSampleData',
        'Magento_TaxImportExport',
        'Magento_TaxSampleData',
        'Magento_GoogleAdwords',
        'Magento_CmsSampleData',
        'Magento_Translation',
        'Magento_Shipping',
        'Magento_Ups',
        'Magento_UrlRewrite',
        'Magento_CatalogRuleSampleData',
        'Magento_Usps',
        'Magento_Variable',
        'Magento_Version',
        'Magento_Webapi',
        'Magento_SalesRuleSampleData',
        'Magento_CatalogWidget',
        'Magento_WidgetSampleData',
        'Magento_Wishlist',
        'Magento_WishlistSampleData'
    ];
    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param TransportBuilder $transportBuilder
     * @param Loader $moduleLoader
     * @param Config $paymentConfig
     * @param ModuleListInterface $moduleList
     * @param ProductMetadata $productMetadata
     * @internal param ScopeConfigInterface $scopePool
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        TransportBuilder $transportBuilder,
        Loader $moduleLoader,
        Config $paymentConfig,
        ModuleListInterface $moduleList,
        ProductMetadata $productMetadata
    ) {
        $this->moduleList = $moduleList;
        $this->scopeConfig = $scopeConfig;
        $this->_transportBuilder = $transportBuilder;
        $this->_moduleLoader = $moduleLoader;
        $this->_paymentConfig = $paymentConfig;
        $this->productMetadata = $productMetadata;
    }

    /**
     * @param DataObject $postObject
     *
     * @return bool
     * @throws \Exception
     */
    public function sendrequest($postObject)
    {
        if (!filter_var($postObject->getData('to'), FILTER_VALIDATE_EMAIL)) {
            throw new \Exception(__('Please enter a valid e-mail address.'));
        }

        if (strlen(trim($postObject->getData('replyto')))) {
            if (!filter_var($postObject->getData('replyto'), FILTER_VALIDATE_EMAIL)) {
                throw new \Exception(__('Please enter a valid e-mail address (reply to).'));
            }
            $this->_transportBuilder->setReplyTo(trim($postObject->getData('replyto')));
        }

        $sender = [
            'name' => $this->scopeConfig->getValue('trans_email/ident_general/name'),
            'email' => $this->scopeConfig->getValue('trans_email/ident_general/email'),
        ];

        if (!strlen($sender['email'])) {
            throw new \Exception('Please set your shop e-mail address!');
        }

        $modules = [];
        foreach ($this->_moduleLoader->load() as $module) {
            if (!in_array($module['name'], $this->_moduleBlacklist)) {
                $modules[] = $module['name'];
            }
        }
        natsort($modules);

        $payments = $this->_paymentConfig->getActiveMethods();

        $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $foreign = [];
        $mine = [];
        foreach ($payments as $paymentCode => $paymentModel) {
            $method = [
                'value' => $paymentCode,
                'config' => []
            ];

            if (preg_match('/^wirecard_elasticengine/i', $paymentCode)) {
                $method['config'] = $this->scopeConfig->getValue('payment/' . $paymentCode, $scope);
                $mine[$paymentCode] = $method;
            } else {
                $foreign[$paymentCode] = $method;
            }
        }

        $versioninfo = new \Magento\Framework\DataObject();
        $versioninfo->setData([
            'product' => 'Magento2',
            'productVersion' => $this->productMetadata->getVersion(),
            'pluginName' => 'Wirecard_ElasticEngine',
            'pluginVersion' => $this->moduleList->getOne('Wirecard_ElasticEngine')['setup_version']
        ]);

        $transport = $this->_transportBuilder
            ->setTemplateIdentifier('contact_support_email')
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_ADMINHTML,
                    'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                ]
            )
            ->setTemplateVars([
                'data' => $postObject,
                'modules' => $modules,
                'foreign' => $foreign,
                'mine' => $mine,
                'configstr' => $this->getConfigString(),
                'versioninfo' => $versioninfo
            ])
            ->setFrom($sender)
            ->addTo($postObject->getData('to'))
            ->getTransport();

        $transport->sendMessage();

        return true;
    }

    /**
     * @return string
     */
    private function getConfigString()
    {
        $config = $this->scopeConfig->getValue(
            'wirecard_elasticengine/credentials',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $config_str = "";

        foreach ($config as $key => $value) {
            if (in_array($key, ['pass'])) {
                continue;
            }
            $config_str .= "[$key] = $value\n";
        }

        return $config_str;
    }
}
