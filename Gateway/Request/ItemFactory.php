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

use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use Wirecard\ElasticEngine\Gateway\Helper\CalculationTrait;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Item;
use Wirecard\PaymentSdk\Exception\MandatoryFieldMissingException;

/**
 * Class ItemFactory
 * @package Wirecard\ElasticEngine\Gateway\Request
 */
class ItemFactory
{
    use CalculationTrait;

    /**
     * @param OrderItemInterface $magentoItemObj
     * @param string $currency
     * @return Item
     * @throws \InvalidArgumentException
     * @throws MandatoryFieldMissingException
     *
     * @since 1.5.3 Use divide method to prevent division by zero
     */
    public function create($magentoItemObj, $currency)
    {
        if (!$magentoItemObj instanceof OrderItemInterface) {
            throw new \InvalidArgumentException('Item data object should be provided.');
        }

        $qty         = $magentoItemObj->getQtyOrdered();
        $qtyAmount   = $magentoItemObj->getPrice();
        $qtyTax      = $this->divide($magentoItemObj->getTaxAmount(), $qty);
        $qtyDiscount = $this->divide($magentoItemObj->getDiscountAmount(), $qty);

        $amount = $qtyAmount + $qtyTax - $qtyDiscount;
        $name = $magentoItemObj->getName();

        $item = $this->createSdkItem(
            $name,
            $amount,
            $currency,
            $qty,
            $qtyTax,
            $magentoItemObj
        );

        return $item;
    }

    /**
     * @param Order\Item $magentoItemObj
     * @param string $currency
     * @param int $qty
     * @return Item
     * @throws \InvalidArgumentException
     *
     * @since 1.5.3 Use divide method to prevent division by zero
     */
    public function capture($magentoItemObj, $currency, $qty)
    {
        if (!$magentoItemObj instanceof Order\Item) {
            throw new \InvalidArgumentException('Item data object should be provided.');
        }

        //Invoiceamount per quantity
        $qtyAmount   = $this->divide($magentoItemObj->getBaseRowInvoiced(), $magentoItemObj->getQtyInvoiced());
        $qtyTax      = $this->divide($magentoItemObj->getTaxInvoiced(), $magentoItemObj->getQtyInvoiced());
        $qtyDiscount = $this->divide($magentoItemObj->getDiscountInvoiced(), $magentoItemObj->getQtyInvoiced());

        $amount = $qtyAmount + $qtyTax - $qtyDiscount;
        $name = $magentoItemObj->getName();

        //Rounding issue
        if (strlen(substr(strrchr((string)$amount, "."), 1)) > 2) {
            $amount = number_format($amount * $qty, 2);
            $name .= ' x' . $qty;
            $qtyTax = $qtyTax * $qty;
            $qty = 1;
        }

        $item = $this->createSdkItem(
            $name,
            $amount,
            $currency,
            $qty,
            $qtyTax,
            $magentoItemObj
        );

        return $item;
    }

    /**
     * @param Order\Item $magentoItemObj
     * @param string $currency
     * @param int $qty
     * @return Item
     *
     * @since 1.5.3 Use divide method to prevent division by zero
     */
    public function refund($magentoItemObj, $currency, $qty)
    {
        if (!$magentoItemObj instanceof Order\Item) {
            throw new \InvalidArgumentException('Item data object should be provided.');
        }

        //Refundamount per quantity
        $qtyAmount   = $this->divide($magentoItemObj->getAmountRefunded(), $magentoItemObj->getQtyRefunded());
        $qtyTax      = $this->divide($magentoItemObj->getTaxRefunded(), $magentoItemObj->getQtyRefunded());
        $qtyDiscount = $this->divide($magentoItemObj->getDiscountRefunded(), $magentoItemObj->getQtyRefunded());

        $amount = $qtyAmount + $qtyTax - $qtyDiscount;
        $name = $magentoItemObj->getName();

        //Rounding issue
        if (strlen(substr(strrchr((string)$amount, "."), 1)) > 2) {
            $amount = number_format($amount * $qty, 2);
            $name .= ' x' . $qty;
            $qtyTax = $qtyTax * $qty;
            $qty = 1;
        }

        $item = $this->createSdkItem(
            $name,
            $amount,
            $currency,
            $qty,
            $qtyTax,
            $magentoItemObj
        );

        return $item;
    }

    /**
     * @param string $name
     * @param float $amount
     * @param string $currency
     * @param int $qty
     * @param float $qtyTax
     * @param Order\Item|OrderItemInterface $magentoItem
     * @return Item
     *
     * @since 1.5.3
     */
    protected function createSdkItem($name, $amount, $currency, $qty, $qtyTax, $magentoItem)
    {
        $item = new Item(
            $name,
            new Amount((float)$amount, $currency),
            (int) $qty
        );

        $item->setDescription($magentoItem->getDescription());
        $item->setArticleNumber($magentoItem->getSku());
        $item->setTaxRate($this->calculateTax(
            $qtyTax,
            $amount
        ));

        return $item;
    }
}
