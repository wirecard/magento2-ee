<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Helper;

trait CalculationTrait
{
    /**
     * Divide method to prevent division by zero
     * Returns 0 in that case
     *
     * @param float|int $dividend
     * @param float|int $divisor
     * @return float
     *
     * @since 1.5.4 Add float cast to divisor
     * @since 1.5.3
     */
    public function divide($dividend, $divisor)
    {
        if (empty((float)$divisor)) {
            return (float)0;
        }

        return (float)($dividend / $divisor);
    }

    /**
     * Return the tax rate
     *
     * @param float $taxAmount amount of tax
     * @param float $grossAmount total amount
     * @param int    $decimals decimals used for number_format
     * @return string tax rate, rounded to 2 decimals
     *
     * @since 1.5.3
     */
    public function calculateTax($taxAmount, $grossAmount, $decimals = 2)
    {
        return number_format(
            $this->divide($taxAmount, $grossAmount) * 100,
            $decimals
        );
    }
}
