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

    protected $customerSession;

    protected $transactionServiceFactory;

    protected $taxCalculation;

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
        \Magento\Customer\Model\Session $customerSession,
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
        $this->customerSession = $customerSession;
        $this->taxCalculation = $taxCalculation;
        $this->resolver = $resolver;
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
        if ($this->customerSession->isLoggedIn()) {
            return $this->buildErrorResponse('a customer is logged on, no quote available', ['session' => $this->customerSession]);
        }

        $requestQuoteId = $this->getRequest()->getParam('quoteId', null);
        if (is_null($requestQuoteId)) {
            return $this->buildErrorResponse('no quore id given');
        }

        $seamlessRequestData = null;
        $quote = $this->fetchQuote($requestQuoteId);
        if (is_null($quote)) {
            return $this->buildErrorResponse('quote not found', ['quoteId' => $requestQuoteId]);
        }

        $quote->reserveOrderId();

        $orderDto = new OrderDto();
        $orderDto->quote = $quote;
        $transactionService = $this->transactionServiceFactory->create(CreditCardTransaction::NAME);
        $orderDto->orderId = $quote->getReservedOrderId();

        $method = $this->paymentHelper->getMethodInstance('wirecard_elasticengine_creditcard');
        $baseUrl = $method->getConfigData('base_url');
        $language = $this->getSupportedHppLangCode($baseUrl);

        $orderDto->config = $transactionService->getConfig()->get(CreditCardTransaction::NAME);
        $this->processCreditCard($orderDto);
        $data = $transactionService->getCreditCardUiWithData($orderDto->transaction, 'authorization', $language);
        $decodedData = json_decode($data);
        if (empty($decodedData)) {
            return $this->buildErrorResponse('cannot create UI', ['quoteId' => $requestQuoteId, 'raw ui data' => $data]);
        }

        return $this->buildSuccessResponse($decodedData);
    }

    private function buildSuccessResponse($uiData) {
        $jsonResponse = $this->resultJsonFactory->create();
        $jsonResponse->setData([
            'status' => 'OK',
            'uiData' => $uiData
        ]);
        return $jsonResponse;
    }

    private function buildErrorResponse($errMsg, $details = []) {
        $jsonResponse = $this->resultJsonFactory->create();
        $errData = [
            'status' => 'ERR',
            'errMsg' => $errMsg,
        ];
        if (!empty($details)) {
            $errData['details'] = $details;
        }

        $jsonResponse->setData($errData);
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

    private function processCreditCard(OrderDTO $orderDto)
    {
        $orderDto->transaction = new CreditCardTransaction();
        $orderDto->transaction->setConfig($orderDto->config);
        $currency = $orderDto->quote->getBaseCurrencyCode();
        $orderDto->amount = new Amount($orderDto->quote->getGrandTotal(), $currency);
        $orderDto->transaction->setAmount($orderDto->amount);

        $orderDto->customFields = new CustomFieldCollection();
        $orderDto->customFields->add(new CustomField('orderId', $orderDto->orderId));
        $orderDto->transaction->setCustomFields($orderDto->customFields);

        $orderDto->transaction->setEntryMode('ecommerce');
        $orderDto->transaction->setLocale(substr($this->resolver->getLocale(), 0, 2));

        $cfgkey = $orderDto->transaction->getConfigKey();
        $wdBaseUrl = $this->urlBuilder->getRouteUrl('wirecard_elasticengine');

        $methodAppend = '?method=' . urlencode($cfgkey);

        $orderDto->transaction->setRedirect(new \Wirecard\PaymentSdk\Entity\Redirect(
            $wdBaseUrl . 'frontend/redirect' . $methodAppend,
            $wdBaseUrl . 'frontend/cancel' . $methodAppend,
            $wdBaseUrl . 'frontend/redirect' . $methodAppend
        ));
        $notificationUrl = $wdBaseUrl . 'frontend/notify?orderId=' . $orderDto->orderId;
        $orderDto->transaction->setNotificationUrl($notificationUrl);

        if ($this->methodConfig->getValue('send_additional')) {
            $this->setAdditionalInformation($orderDto);
        }
    }

    public function setAdditionalInformation(OrderDto $orderDto)
    {
        $orderDto->basket = new Basket();

        $orderDto->transaction->setAccountHolder($this->fetchAccountHolder($orderDto->quote->getBillingAddress()));

        $shippingAddress = $orderDto->quote->getShippingAddress();
        if (isset($shippingAddress)) {
            $orderDto->transaction->setShipping(
                $this->fetchAccountHolder($orderDto->quote->getShippingAddress())
            );
        }

        $orderDto->transaction->setDescriptor(sprintf(
            '%s %s',
            substr($this->storeManager->getStore()->getName(), 0, 9),
            $orderDto->orderId
        ));

        $orderDto->transaction->setOrderNumber($orderDto->orderId);
        $orderDto->basket = new Basket();
        $this->addOrderItemsToBasket($orderDto);
        $orderDto->transaction->setIpAddress($orderDto->quote->getRemoteIp());
        $orderDto->transaction->setConsumerId($orderDto->quote->getCustomerId());
    }

    private function fetchAccountHolder($address) {
        $accountHolder = new AccountHolder();
        $accountHolder->setEmail($address->getEmail());
        $accountHolder->setPhone($address->getTelephone());

        $sdkAddress = new Address($address->getCountryId(), $address->getCity(), $address->getStreetFull());
        $accountHolder->setAddress($sdkAddress);

        return $accountHolder;
    }

    private function addOrderItemsToBasket(OrderDto $orderDto) {
        $items    = $orderDto->quote->getAllVisibleItems();
        $currency = $orderDto->quote->getBaseCurrencyCode();
        foreach ($items as $orderItem) {
            $amount    = new Amount($orderItem->getPriceInclTax(), $currency);
            $taxAmount = new Amount($orderItem->getTaxAmount(), $currency);
            $item = new Item($orderItem->getName(), $amount, $orderItem->getQty());
            $item->setTaxAmount($taxAmount);
            $item->setTaxRate($this->calculateTax($orderItem->getTaxAmount(), $orderItem->getPriceInclTax()));
            $orderDto->basket->add($item);
        }
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
