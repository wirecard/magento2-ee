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
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Item;
use Wirecard\PaymentSdk\Exception\MandatoryFieldMissingException;

/**
 * Class ItemFactory
 * @package Wirecard\ElasticEngine\Gateway\Request
 */
class ItemFactory
{
    /**
     * @param OrderItemInterface $magentoItemObj
     * @param string $currency
     * @return Item
     * @throws \InvalidArgumentException
     * @throws MandatoryFieldMissingException
     */
    public function create($magentoItemObj, $currency)
    {
        if (!$magentoItemObj instanceof OrderItemInterface) {
            throw new \InvalidArgumentException('Item data object should be provided.');
        }

        $amount = $magentoItemObj->getPriceInclTax();
        $qty = $magentoItemObj->getQtyOrdered();
        $name = $magentoItemObj->getName();
        $taxAmount = $magentoItemObj->getTaxAmount()/$magentoItemObj->getQtyOrdered();

        if ($amount * $qty !== $magentoItemObj->getBaseRowTotalInclTax()) {
            $amount = $magentoItemObj->getBaseRowTotalInclTax();
            $name .= ' x' . $qty;
            $qty = 1;
            $taxAmount = $magentoItemObj->getTaxAmount();
        }

        $taxRate = $taxAmount / $amount;
        $item = new Item(
            $name,
            new Amount($amount, $currency),
            $qty
        );
        $item->setDescription($magentoItemObj->getDescription());
        $item->setArticleNumber($magentoItemObj->getSku());
        $item->setTaxRate(number_format($taxRate * 100, 2));

        return $item;
    }

    /**
     * @param Order\Item $magentoItemObj
     * @param string $currency
     * @param int $qty
     * @return Item
     * @throws \InvalidArgumentException
     */
    public function capture($magentoItemObj, $currency, $qty)
    {
        if (!$magentoItemObj instanceof Order\Item) {
            throw new \InvalidArgumentException('Item data object should be provided.');
        }

        //Invoiceamount per quantity
        $amount = $magentoItemObj->getBaseRowInvoiced()/$magentoItemObj->getQtyInvoiced();
        $name = $magentoItemObj->getName();
        $taxAmount = $magentoItemObj->getTaxInvoiced();

        $taxRate = $taxAmount / $amount;
        $item = new Item(
            $name,
            new Amount($amount, $currency),
            $qty
        );
        $item->setDescription($magentoItemObj->getDescription());
        $item->setArticleNumber($magentoItemObj->getSku());
        $item->setTaxRate(number_format($taxRate * 100, 2));

        return $item;
    }

    /**
     * @param Order\Item $magentoItemObj
     * @param string $currency
     * @param int $qty
     * @return Item
     */
    public function refund($magentoItemObj, $currency, $qty)
    {
        if (!$magentoItemObj instanceof Order\Item) {
            throw new \InvalidArgumentException('Item data object should be provided.');
        }

        //Refundamount per quantity
        $amount = $magentoItemObj->getBaseAmountRefunded()/$magentoItemObj->getQtyRefunded();
        $name = $magentoItemObj->getName();
        $taxAmount = $magentoItemObj->getTaxRefunded();

        $taxRate = $taxAmount / $amount;
        $item = new Item(
            $name,
            new Amount($amount, $currency),
            $qty
        );
        $item->setDescription($magentoItemObj->getDescription());
        $item->setArticleNumber($magentoItemObj->getSku());
        $item->setTaxRate(number_format($taxRate * 100, 2));

        return $item;
    }
}
