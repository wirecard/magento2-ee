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

use Magento\Bundle\Model\Product\Price;
use Magento\Catalog\Model\Product\Type;
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
 *
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
            if (!$this->canAddToBasket($item)) {
                continue;
            }

            $basket->add($this->itemFactory->create($item, $order->getCurrencyCode()));
        }

        $orderObject = $this->checkoutSession->getQuote()->getShippingAddress();

        if ($orderObject->getShippingInclTax() > 0) {
            $shippingItem = new Item(
                'Shipping',
                new Amount((float)$orderObject->getShippingInclTax(), $order->getCurrencyCode()),
                1
            );

            $taxRate = number_format(
                ($orderObject->getShippingTaxAmount() / $orderObject->getShippingInclTax()) * 100,
                    2
            );
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
            throw new NoSuchEntityException(__('no_such_order_error'));
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
            $basket->add($this->itemFactory->capture($item, $order->getCurrencyCode(), (int) $qty));
        }

        //Current shipping
        $origShipping = $orderObject->getOrigData('shipping_invoiced');
        $newShipping = $orderObject->getShippingInclTax();
        $shipping = $newShipping - $origShipping;

        if ($shipping > 0) {
            $shippingItem = new Item(
                'Shipping',
                new Amount((float)$shipping, $order->getCurrencyCode()),
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
            throw new NoSuchEntityException(__('no_such_order_error'));
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
            $basket->add($this->itemFactory->refund($item, $order->getCurrencyCode(), (int) $qty));
        }

        //Current shipping
        $origShipping = $orderObject->getOrigData('shipping_refunded');
        $newShipping = $orderObject->getShippingRefunded();
        $shipping = $newShipping - $origShipping;

        if ($shipping > 0) {
            $shippingItem = new Item(
                'Shipping',
                new Amount((float)$shipping, $order->getCurrencyCode()),
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
     * @throws MandatoryFieldMissingException
     */
    public function void($order, $transaction)
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
            throw new NoSuchEntityException(__('no_such_order_error'));
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

        if ($orderObject->getShippingInclTax() > 0) {
            $shippingItem = new Item(
                'Shipping',
                new Amount((float)$orderObject->getShippingInclTax(), $order->getCurrencyCode()),
                1
            );

            $taxRate = number_format(
                ($orderObject->getShippingTaxAmount() / $orderObject->getShippingInclTax()) * 100,
                2
            );
            $shippingItem->setDescription($orderObject->getShippingDescription());
            $shippingItem->setArticleNumber($orderObject->getShippingMethod());
            $shippingItem->setTaxRate($taxRate);
            $basket->add($shippingItem);
        }
        return $basket;
    }

    /**
     * check whether item should be added to the basket
     * there are two possibilty of price calculation for bundle products:
     * - fixed: the price is set in the bundle product
     * - dynamic: the price is calculated based on the bundled products
     *
     * For fixed pricing bundle products, the bundle product itself must be added to the basket,
     * but not the bundled products.
     *
     * For dynamic pricing bundle products, only the bundled products must be added.
     *
     * @param Order\Item $item
     *
     * @return bool
     */
    protected function canAddToBasket($item)
    {
        // items with no price are excluded
        if ($item->getPriceInclTax() == 0) {
            return false;
        }

        // bundles with dynamic pricing must not be added to the basket
        // price is dynamically calculated based on the bundled-items
        if ($item->getProductType() == Type::TYPE_BUNDLE
            && $item->getProduct()->getPriceType() == Price::PRICE_TYPE_DYNAMIC) {
            return false;
        }

        // item has no parent, can be safely added to the basket
        if ($item->getParentItem() === null) {
            return true;
        }

        // if parent is not a product bundle dont add it to the basket
        if ($item->getParentItem()->getProductType() != Type::TYPE_BUNDLE) {
            return false;
        }

        // if item is part of a product bundle with fixed pricing dont add it
        // price is set directly in the bundle product
        if ($item->getParentItem()->getProductType() == Type::TYPE_BUNDLE
            && $item->getParentItem()->getProduct()->getPriceType() == Price::PRICE_TYPE_FIXED
        ) {
            return false;
        }

        return true;
    }
}
