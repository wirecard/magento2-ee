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

namespace Wirecard\ElasticEngine\Gateway\Request;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\Item;
use Wirecard\PaymentSdk\Exception\MandatoryFieldMissingException;
use Wirecard\PaymentSdk\Transaction\Transaction;

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
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * BasketFactory constructor.
     * @param ItemFactory $itemFactory
     * @param Session $checkoutSession
     * @param OrderFactory $orderFactory
     */
    public function __construct(ItemFactory $itemFactory, Session $checkoutSession, OrderFactory $orderFactory)
    {
        $this->itemFactory = $itemFactory;
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @param OrderAdapterInterface $order
     * @param Transaction $transaction
     * @return Basket
     * @throws \InvalidArgumentException
     * @throws NoSuchEntityException
     * @throws MandatoryFieldMissingException
     */
    public function create($order, $transaction)
    {
        if (!$order instanceof OrderAdapterInterface) {
            throw new \InvalidArgumentException('Order data obj should be provided.');
        }

        $basket = new Basket();
        $basket->setVersion($transaction);
        $items = $order->getItems();

        /** @var OrderItemInterface $item*/
        foreach ($items as $item) {
            if ($item->getPriceInclTax() == 0) {
                continue;
            }
            $basket->add($this->itemFactory->create($item, $order->getCurrencyCode()));
        }

        $orderObject = $this->checkoutSession->getQuote()->getShippingAddress();

        if ($orderObject->getShippingInclTax() > 0) {
            $shippingItem = new Item(
                'Shipping',
                new Amount($orderObject->getShippingInclTax(), $order->getCurrencyCode()),
                1
            );

            $taxRate = number_format(($orderObject->getShippingTaxAmount() / $orderObject->getShippingInclTax()) * 100,
                    2);
            $shippingItem->setDescription($orderObject->getShippingDescription());
            $shippingItem->setArticleNumber($orderObject->getShippingMethod());
            $shippingItem->setTaxRate($taxRate);
            $basket->add($shippingItem);
        }
        return $basket;
    }

    /**
     * @param OrderAdapterInterface $order
     * @param Transaction $transaction
     * @return Basket
     * @throws \InvalidArgumentException
     * @throws NoSuchEntityException
     */
    public function capture($order, $transaction)
    {
        if (!$order instanceof OrderAdapterInterface) {
            throw new \InvalidArgumentException('Order data obj should be provided.');
        }

        $orderId = $order->getId();

        /** @var Order $orderObject */
        $orderObject = $this->orderFactory->create();
        if (!is_null($orderObject)) {
            $orderObject->load($orderId);
        }

        if (is_null($orderObject)) {
            throw new NoSuchEntityException(__('No such order found.'));
        }

        $basket = new Basket();
        $basket->setVersion($transaction);
        $items = $order->getItems();

        /** @var Order\Item $item*/
        foreach ($items as $item) {
            //Current quantity for item
            $origQty = $item->getOrigData('qty_invoiced');
            $newQty = $item->getQtyInvoiced();
            $qty = $newQty - $origQty;
            if ($item->getBaseRowInvoiced() == 0 || $qty == 0) {
                continue;
            }
            $basket->add($this->itemFactory->capture($item, $order->getCurrencyCode(), $qty));
        }

        //Current shipping
        $origShipping = $orderObject->getOrigData('shipping_invoiced');
        $newShipping = $orderObject->getShippingInclTax();
        $shipping = $newShipping - $origShipping;

        if ($shipping > 0) {
            $shippingItem = new Item(
                'Shipping',
                new Amount($shipping, $order->getCurrencyCode()),
                1
            );

            $shippingItem->setDescription($orderObject->getShippingDescription());
            $shippingItem->setArticleNumber($orderObject->getShippingMethod());
            $shippingItem->setTaxRate(number_format(0, 2));
            $basket->add($shippingItem);
        }
        return $basket;
    }

    /**
     * @param OrderAdapterInterface $order
     * @param Transaction $transaction
     * @return Basket
     * @throws \InvalidArgumentException
     * @throws NoSuchEntityException
     */
    public function refund($order, $transaction)
    {
        if (!$order instanceof OrderAdapterInterface) {
            throw new \InvalidArgumentException('Order data obj should be provided.');
        }

        $orderId = $order->getId();

        /** @var Order $orderObject */
        $orderObject = $this->orderFactory->create();
        if (!is_null($orderObject)) {
            $orderObject->load($orderId);
        }

        if (is_null($orderObject)) {
            throw new NoSuchEntityException(__('No such order found.'));
        }

        $basket = new Basket();
        $basket->setVersion($transaction);
        $items = $order->getItems();

        /** @var Order\Item $item*/
        foreach ($items as $item) {
            //Current quantity for item
            $origQty = $item->getOrigData('qty_refunded');
            $newQty = $item->getQtyRefunded();
            $qty = $newQty - $origQty;
            if ($item->getBaseAmountRefunded() == 0 || $qty == 0) {
                continue;
            }
            $basket->add($this->itemFactory->refund($item, $order->getCurrencyCode(), $qty));
        }

        //Current shipping
        $origShipping = $orderObject->getOrigData('shipping_refunded');
        $newShipping = $orderObject->getShippingRefunded();
        $shipping = $newShipping - $origShipping;

        if ($shipping > 0) {
            $shippingItem = new Item(
                'Shipping',
                new Amount($shipping, $order->getCurrencyCode()),
                1
            );

            $shippingItem->setDescription($orderObject->getShippingDescription());
            $shippingItem->setArticleNumber($orderObject->getShippingMethod());
            $shippingItem->setTaxRate(number_format(0, 2));
            $basket->add($shippingItem);
        }
        return $basket;
    }
}
