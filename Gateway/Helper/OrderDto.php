<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Helper;

use Magento\Quote\Model\Quote;
use Wirecard\PaymentSdk\Config\CreditCardConfig;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;

/**
 * A simple Data Transfer Object (Dto) to bundle related information
 * All properties are public, there is no logic in this class.
 */
class OrderDto
{
    // Billing details
    /** @var string */
    public $billingMail;
    /** @var string */
    public $billingPhone;
    /** @var string */
    public $billingBirthDate;
    /** @var string */
    public $billingCountryCode;
    /** @var string */
    public $billingCity;
    /** @var string */
    public $billingStreet;

    // General properties
    /** @var string */
    public $orderId;
    /** @var string */
    public $redirect;
    /** @var string */
    public $notificationUrl;

    // objects
    /** @var Basket */
    public $basket;
    /** @var CustomFieldCollection */
    public $customFields;
    /** @var CreditCardTransaction */
    public $transaction;
    /** @var AccountHolder */
    public $accountHolder;
    /** @var AccountHolder */
    public $shippingAccountHolder;
    /** @var Amount */
    public $amount;
    /** @var Quote Magento2 quote stores the current shopping cart */
    public $quote;
    /** @var CreditCardConfig */
    public $config;
}
