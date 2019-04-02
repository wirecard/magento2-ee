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
 *
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
