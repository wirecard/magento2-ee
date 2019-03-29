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

namespace Wirecard\ElasticEngine\Controller\Frontend;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Tax\Model\Calculation;
use Magento\Payment\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Wirecard\ElasticEngine\Gateway\Config\PaymentSdkConfigFactory;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\CreditCardConfig;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Address;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Item;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\ElasticEngine\Gateway\Helper\OrderDto;
use Magento\Payment\Gateway\ConfigInterface;

class Creditcard extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    protected $quoteIdMaskFactory;

    protected $transactionServiceFactory;

    protected $taxCalculation;

    protected $orderDto;

    protected $resolver;

    protected $storeManager;

    protected $urlBuilder;

    protected $paymentHelper;

    protected $methodConfig;

    /**
     * Creditcard constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory $transactionServiceFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
     * @param Calculation $taxCalculation
     * @param ResolverInterface $resolver
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlBuilder
     * @param Data $paymentHelper
     * @param ConfigInterface $methodConfig
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory $transactionServiceFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        Calculation $taxCalculation,
        ResolverInterface $resolver,
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        Data $paymentHelper,
        ConfigInterface $methodConfig
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->transactionServiceFactory = $transactionServiceFactory;
        $this->quoteRepository = $quoteRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->taxCalculation = $taxCalculation;
        $this->resolver = $resolver;
        $this->orderDto = new OrderDto();
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->paymentHelper = $paymentHelper;
        $this->methodConfig = $methodConfig;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {

        $requestQuoteId = $this->getRequest()->getParam('quoteId', null);

        if (is_null($requestQuoteId)) {
            return null;
        }

        $seamlessRequestData = null;
        $quote = $this->fetchQuote($requestQuoteId);

        if (is_null($quote)) {
            return null;
        }

        $quote->reserveOrderId();

        $this->orderDto->quote = $quote;
        $transactionService = $this->transactionServiceFactory->create(CreditCardTransaction::NAME);
        $this->orderDto->orderId = $quote->getReservedOrderId();

        $method = $this->paymentHelper->getMethodInstance('wirecard_elasticengine_creditcard');
        $baseUrl = $method->getConfigData('base_url');
        $language = $this->getSupportedHppLangCode($baseUrl);

        $this->orderDto->config = $transactionService->getConfig()->get(CreditCardTransaction::NAME);
        $this->processCreditCard();
        $data = $transactionService->getCreditCardUiWithData($this->orderDto->transaction, 'authorization', $language);
        $jsonResponse = $this->resultJsonFactory->create();
        $jsonResponse->setData($data);

        return $jsonResponse;
    }

    private function calculateTax($taxAmount, $grossAmount) {
        return number_format(
            ($taxAmount / $grossAmount) * 100,
            2
        );
    }

    private function fetchQuote($maskedQuoteId) {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($maskedQuoteId, 'masked_id');
        $quoteId = $quoteIdMask->getQuoteId();

        try {
            $quote = $this->quoteRepository->get($quoteId);
        } catch(NoSuchEntityException $e) {
            $quote = null;
        }

        return $quote;
    }

    public function processCreditCard()
    {
        $this->orderDto->transaction = new CreditCardTransaction();
        $this->orderDto->transaction->setConfig($this->orderDto->config);
        $this->orderDto->amount = $this->getAmount($this->orderDto->quote->getGrandTotal());
        $this->orderDto->transaction->setAmount($this->orderDto->amount);

        $this->orderDto->customFields = new CustomFieldCollection();
        $this->orderDto->customFields->add(new CustomField('orderId', $this->orderDto->orderId));
        $this->orderDto->transaction->setCustomFields($this->orderDto->customFields);

        $this->orderDto->transaction->setEntryMode('ecommerce');
        $this->orderDto->transaction->setLocale(substr($this->resolver->getLocale(), 0, 2));

        $cfgkey = $this->orderDto->transaction->getConfigKey();
        $wdBaseUrl = $this->urlBuilder->getRouteUrl('wirecard_elasticengine');

        $methodAppend = '?method=' . urlencode($cfgkey);

        $this->orderDto->transaction->setRedirect(new \Wirecard\PaymentSdk\Entity\Redirect(
            $wdBaseUrl . 'frontend/redirect' . $methodAppend,
            $wdBaseUrl . 'frontend/cancel' . $methodAppend,
            $wdBaseUrl . 'frontend/redirect' . $methodAppend
        ));
        $this->orderDto->transaction->setNotificationUrl($wdBaseUrl . 'frontend/notify?orderId=' . $this->orderDto->orderId);

        if ($this->methodConfig->getValue('send_additional')) {
            $this->setAdditionalInformation();
        }
    }

    public function setAdditionalInformation()
    {
        $this->orderDto->basket = new Basket();

        $this->orderDto->transaction->setAccountHolder(
            $this->fetchAccountHolder($this->orderDto->quote->getBillingAddress())
        );

        $shippingAddress = $this->orderDto->quote->getShippingAddress();
        if (isset($shippingAddress)) {
            $this->orderDto->transaction->setShipping(
                $this->fetchAccountHolder($this->orderDto->quote->getShippingAddress())
            );
        }

        $this->orderDto->transaction->setDescriptor(sprintf(
            '%s %s',
            substr($this->storeManager->getStore()->getName(), 0, 9),
            $this->orderDto->orderId
        ));

        $this->orderDto->transaction->setOrderNumber($this->orderDto->orderId);
        $this->orderDto->basket = new Basket();
        $this->addOrderItemsToBasket();
        $this->orderDto->transaction->setIpAddress($this->orderDto->quote->getRemoteIp());
        $this->orderDto->transaction->setConsumerId($this->orderDto->quote->getCustomerId());
    }

    private function fetchAccountHolder($address) {
        $accountHolder = new AccountHolder();
        $accountHolder->setEmail($address->getEmail());
        $accountHolder->setPhone($address->getTelephone());

        $sdkAddress = new Address($address->getCountryId(), $address->getCity(), $address->getStreetFull());
        $accountHolder->setAddress($sdkAddress);

        return $accountHolder;
    }

    private function addOrderItemsToBasket() {
        $items = $this->orderDto->quote->getAllVisibleItems();

        foreach ($items as $orderItem) {
            $amount = $this->getAmount($orderItem->getPriceInclTax());
            $taxAmount = $this->getAmount($orderItem->getTaxAmount());
            $item = new Item($orderItem->getName(), $amount, $orderItem->getQty());
            $item->setTaxAmount($taxAmount);
            $item->setTaxRate($this->calculateTax($orderItem->getTaxAmount(), $orderItem->getPriceInclTax()));
            $this->orderDto->basket->add($item);
        }
    }

    private function getAmount($amount) {
        return new Amount($amount, $this->orderDto->quote->getBaseCurrencyCode());
    }

    private function getSupportedHppLangCode($baseUrl)
    {
        $locale = $this->resolver->getLocale();
        $lang = 'en';
        //special case for chinese languages
        switch ($locale) {
            case 'zh_Hans_CN':
                $locale = 'zh_CN';
                break;
            case 'zh_Hant_TW':
                $locale = 'zh_TW';
                break;
            default:
                break;
        }
        try {
            $supportedLang = json_decode(file_get_contents($baseUrl . '/engine/includes/i18n/languages/hpplanguages.json'));
            if (key_exists(substr($locale, 0, 2), $supportedLang)) {
                $lang = substr($locale, 0, 2);
            } elseif (key_exists($locale, $supportedLang)) {
                $lang = $locale;
            }
        } catch (\Exception $exception) {
            return 'en';
        }
        return $lang;
    }
}
