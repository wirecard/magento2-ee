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

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Helper\Data;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Calculation;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Gateway\Helper\OrderDto;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\ElasticEngine\Model\Adminhtml\Source\PaymentAction;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Address;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Item;
use Wirecard\PaymentSdk\Entity\Redirect;

class Creditcard extends Action
{
    /*' @var string FORM parameter name to send the transaction type in AJAX */
    const FRONTEND_DATAKEY_TXTYPE = 'txtype';

    /** @var string key CREDITCARD as sent by frontend */
    const FRONTEND_CODE_CREDITCARD = 'wirecard_elasticengine_creditcard';

    /** @var string key UnionPayInternational as sent by frontend */
    const FRONTEND_CODE_UPI = 'wirecard_elasticengine_unionpayinternational';

    /** @var JsonFactory Magento2 JsonFactory injected by DI */
    protected $resultJsonFactory;

    /** @var CartRepositoryInterface Magento2 cart repository injected by DI */
    protected $cartRepository;

    /** @var Session Magento2 checkout session injected by DI */
    protected $checkoutSession;

    /** @var TransactionServiceFactory paymentSDK TransactionService injected by DI */
    protected $transactionServiceFactory;

    /** @var Calculation Magneto2 tax calculator injected by DI */
    protected $taxCalculation;

    /** @var ResolverInterface Magento2 Resolver injected by DI */
    protected $resolver;

    /** @var StoreManagerInterface Magento2 StoreManager injected by DI */
    protected $storeManager;

    /** @var UrlInterface Magento2 UrlInterface injected by DI */
    protected $urlBuilder;

    /** @var Data Magento2 PaymentHelper injected by DI */
    protected $paymentHelper;

    /** @var ConfigInterface Magento2 payment method config injected by DI */
    protected $methodConfig;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * Creditcard constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param TransactionServiceFactory $transactionServiceFactory
     * @param CartRepositoryInterface $cartRepository
     * @param Session $checkoutSession
     * @param Calculation $taxCalculation
     * @param ResolverInterface $resolver
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlBuilder
     * @param Data $paymentHelper
     * @param ConfigInterface $methodConfig
     * @param LoggerInterface $logger,
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        TransactionServiceFactory $transactionServiceFactory,
        CartRepositoryInterface $quoteRepository,
        Session $checkoutSession,
        Calculation $taxCalculation,
        ResolverInterface $resolver,
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        Data $paymentHelper,
        ConfigInterface $methodConfig,
        LoggerInterface $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->transactionServiceFactory = $transactionServiceFactory;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->taxCalculation = $taxCalculation;
        $this->resolver = $resolver;
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->paymentHelper = $paymentHelper;
        $this->methodConfig = $methodConfig;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Execute the command to build the CreditCard UI init data
     *
     * Based on the session data about user data and cart information (items, sum)
     * the request data are build to init the CreditCard UI later in JavaScript.
     *
     * The result is JSON with following structure:
     *
     * - success case: status=OK, uiData=(raw JSON data)
     * - error case:   status=ERR, errMsg=error message, optionally details
     *
     * @return Json
     * @throws LocalizedException
     */
    public function execute()
    {
        $quote = $this->checkoutSession->getQuote();
        if (is_null($quote)) {
            $this->logger->warning("Creditcard-UI-AJAX: quote not found");
            return $this->buildErrorResponse('no quote found');
        }

        $txType = $this->getRequest()->getParam(self::FRONTEND_DATAKEY_TXTYPE);
        $txName = $this->findTransactionNameByFrontendType($txType);
        if (is_null($txName)) {
            $this->logger->warning("Creditcard-UI-AJAX: transaction type $txType not supported");
            return $this->buildErrorResponse('Unknown transaction type');
        }

        $orderDto = new OrderDto();
        $quote->reserveOrderId()->save();
        $orderDto->quote = $quote;

        $transactionService = $this->transactionServiceFactory->create($txName);
        $orderDto->orderId = $quote->getReservedOrderId();
        $this->logger->info("Creditcard-UI-AJAX: reserve order id: " . $orderDto->orderId);

        if ($txType === self::FRONTEND_CODE_UPI) {
            $method = $this->paymentHelper->getMethodInstance(self::FRONTEND_CODE_UPI);
        } else {
            $method = $this->paymentHelper->getMethodInstance(self::FRONTEND_CODE_CREDITCARD);
        }
        $this->logger->info($method->getCode());
        $baseUrl = $method->getConfigData('base_url');
        $language = $this->getSupportedHppLangCode($baseUrl);

        $paymentAction = $method->getConfigData('payment_action');
        if ($paymentAction === PaymentAction::AUTHORIZE_CAPTURE) {
            $paymentAction = "purchase";
        } else {
            $paymentAction = "authorization";
        };

        $this->logger->info($paymentAction);
        $this->logger->debug("load config for transaction $txName");
        $orderDto->config = $transactionService->getConfig()->get($txName);
        $this->processCreditCard($orderDto, $txType);
        try {
            $data = $transactionService->getCreditCardUiWithData($orderDto->transaction, $paymentAction, $language);
            if (empty($data)) {
                throw new \Exception("Cannot create UI");
            }
            return $this->buildSuccessResponse($data);
        } catch (\Exception $e) {
            return $this->buildErrorResponse('cannot create UI', ['exception' => get_class($e)]);
        }
    }

    /**
     * Generate the SUCCESS JSON result
     *
     * The resulting JSON contains two keys:
     * - 'status': 'OK' to signalize the JavaScript caller handle answer as init data
     * - 'uiData': the UI init data used by JavaScript code to render the UI
     *
     * @param \stdClass $uiData the JSON payload received from backend
     * @return Json JsonResponse with 'status' and 'uiData'
     */
    private function buildSuccessResponse($uiData)
    {
        $jsonResponse = $this->resultJsonFactory->create();
        $jsonResponse->setData([
            'status' => 'OK',
            'uiData' => $uiData,
        ]);
        return $jsonResponse;
    }

    /**
     * Generate the ERROR JSON result
     *
     * The resulting JSON contains two or three keys:
     * - 'status': 'ERR' to signalize the JavaScript caller handle answer as error
     * - 'errMsg': an english human readable message what's happen
     * - 'details': mapping with additional information (optionally)
     *
     * @param string $errMsg error message for caller
     * @param array $details map with addional information about the problem
     * @return Json JsonResponse with 'status' and 'errMsg'. Can also contains key 'details'
     */
    private function buildErrorResponse($errMsg, $details = [])
    {
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

    /**
     * Return the tax rate
     *
     * @param double $taxAmount amount of tax
     * @param double $grossAmount total amount
     * @return double tax rate, rounded to 2 decimals
     */
    private function calculateTax($taxAmount, $grossAmount)
    {
        return number_format(
            ($taxAmount / $grossAmount) * 100,
            2
        );
    }

    /**
     * Prepare CreditCardTransaction with information stored in $orderDto
     *
     * NOTE: the resulting transaction also stored in the DTO so there is
     *       no return here.
     *
     * @param OrderDto $orderDto data transfer object holds all order data
     * @param string $txType frontend key to specify the transaction type
     */
    private function processCreditCard(OrderDTO $orderDto, string $txType)
    {
        $className = $this->findTransactionClassByFrontendType($txType);
        $orderDto->transaction = new $className();
        $orderDto->transaction->setConfig($orderDto->config);

        $currency         = $orderDto->quote->getBaseCurrencyCode();
        $orderDto->amount = new Amount($orderDto->quote->getGrandTotal(), $currency);
        $orderDto->transaction->setAmount($orderDto->amount);

        $orderDto->customFields = new CustomFieldCollection();
        $orderDto->customFields->add(new CustomField('orderId', $orderDto->orderId));
        $orderDto->transaction->setCustomFields($orderDto->customFields);

        $orderDto->transaction->setEntryMode('ecommerce');
        $orderDto->transaction->setLocale(substr($this->resolver->getLocale(), 0, 2));

        $cfgkey       = $orderDto->transaction->getConfigKey();
        $wdBaseUrl    = $this->urlBuilder->getRouteUrl('wirecard_elasticengine');
        $methodAppend = '?method=' . urlencode($cfgkey);

        $orderDto->transaction->setRedirect(new Redirect(
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

    /**
     * Add additional data to transaction
     *
     * NOTE: the resulting transaction also stored in the DTO so there is
     *       no return here.
     *
     * @param OrderDto $orderDto data transfer object holds all order data
     */
    private function setAdditionalInformation(OrderDto $orderDto)
    {
        $orderDto->basket = new Basket();

        $accountHolder = $this->fetchAccountHolder($orderDto->quote->getBillingAddress());
        $orderDto->transaction->setAccountHolder($accountHolder);

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

    /**
     * Helper method to build the AccountHolder structure by an address
     *
     * @param \Magento\Customer\Model\Address $address Magento2 address from session
     * @return AccountHolder paymentSdk entity AccountHolder
     */
    private function fetchAccountHolder($address)
    {
        $accountHolder = new AccountHolder();
        $accountHolder->setEmail($address->getEmail());
        $accountHolder->setPhone($address->getTelephone());

        $sdkAddress = new Address($address->getCountryId(), $address->getCity(), $address->getStreetFull());
        $accountHolder->setAddress($sdkAddress);

        return $accountHolder;
    }

    /**
     * Build basket based on stored items
     *
     * NOTE: the resulting transaction also stored in the DTO so there is
     *       no return here.
     *
     * @param OrderDto $orderDto data transfer object holds all order data
     */
    private function addOrderItemsToBasket(OrderDto $orderDto)
    {
        $items    = $orderDto->quote->getAllVisibleItems();
        $currency = $orderDto->quote->getBaseCurrencyCode();
        foreach ($items as $orderItem) {
            $amount    = new Amount($orderItem->getPriceInclTax(), $currency);
            $taxAmount = new Amount($orderItem->getTaxAmount(), $currency);
            $item      = new Item($orderItem->getName(), $amount, $orderItem->getQty());
            $item->setTaxAmount($taxAmount);
            $item->setTaxRate($this->calculateTax($orderItem->getTaxAmount(), $orderItem->getPriceInclTax()));
            $orderDto->basket->add($item);
        }
    }

    /**
     * Detect the Transaction type based on the key sent by frontend
     *
     * @param string $txType frontend key to specify the transaction type
     * @return string|null Transaction type name used in backend, or null
     */
    private function findTransactionNameByFrontendType($txType)
    {
        $className = $this->findTransactionClassByFrontendType($txType);
        if (empty($className)) {
            return null;
        }
        return constant("$className::NAME");
    }
    /**
     * Detect the Transaction class for key sent by frontend
     *
     * @param string $txType frontend key to specify the transaction type
     * @return string|null Transaction class name with full namespace, or null
     */
    private function findTransactionClassByFrontendType($txType)
    {
        if (!empty($txType)) {
            switch ($txType) {
                case self::FRONTEND_CODE_CREDITCARD:
                    return '\Wirecard\PaymentSdk\Transaction\CreditCardTransaction';
                case self::FRONTEND_CODE_UPI:
                    return '\Wirecard\PaymentSdk\Transaction\UpiTransaction';
            }
        }

        return null;
    }

    /**
     * Find out the best supported HPP language code
     *
     * Currently, this triggers a call against the EE rest interface to find out
     * all supported languages. Based on the Magento2 locale and the list of the
     *
     * @param string $baseUrl Gateway URL from merchants config
     */
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
