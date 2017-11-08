<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard Central Eastern Europe GmbH
 * (abbreviated to Wirecard CEE) and are explicitly not part of the Wirecard CEE range of
 * products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard CEE does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard CEE does not guarantee their full
 * functionality neither does Wirecard CEE assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard CEE does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

namespace Wirecard\ElasticEngine\Gateway\Request;

use Magento\Checkout\Model\Session;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\Item;
use Wirecard\PaymentSdk\Exception\MandatoryFieldMissingException;

/**
 * Class BasketFactory
 * @package Wirecard\ElasticEngine\Gateway\Request
 */
class BasketFactory
{
    /**
     * @var ItemFactory
     */
    private $itemFactory;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * BasketFactory constructor.
     * @param ItemFactory $itemFactory
     * @param Session $checkoutSession
     */
    public function __construct(ItemFactory $itemFactory, Session $checkoutSession)
    {
        $this->itemFactory = $itemFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param OrderAdapterInterface $order
     * @return Basket
     * @throws \InvalidArgumentException
     * @throws MandatoryFieldMissingException
     */
    public function create($order)
    {
        if (!$order instanceof OrderAdapterInterface) {
            throw new \InvalidArgumentException('Order data obj should be provided.');
        }

        $basket = new Basket();
        $items = $order->getItems();

        foreach ($items as $item) {
            $basket->add($this->itemFactory->create($item, $order->getCurrencyCode()));
        }

        $shippingAddress = $this->checkoutSession->getQuote()->getShippingAddress();

        $shippingItem = new Item(
            'Shipping',
            new Amount($shippingAddress->getShippingInclTax(), $order->getCurrencyCode()),
            1
        );

        $taxRate = number_format(($shippingAddress->getShippingTaxAmount() / $shippingAddress->getShippingInclTax()) * 100,
            2);

        $shippingItem->setDescription($shippingAddress->getShippingDescription());
        $shippingItem->setArticleNumber($shippingAddress->getShippingMethod());
        $shippingItem->setTaxRate($taxRate);
        $basket->add($shippingItem);

        return $basket;
    }
}
