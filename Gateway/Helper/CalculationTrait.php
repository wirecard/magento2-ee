<?php

namespace Wirecard\ElasticEngine\Gateway\Helper;

trait CalculationTrait
{
    /**
     * Divide method to prevent division by zero
     * Returns 0 in that case
     *
     * @param $dividend
     * @param $divisor
     * @return float
     *
     * @since 1.5.3
     */
    public function divide($dividend, $divisor) {
        if (empty($divisor)) {
            return (float)0;
        }

        return (float)($dividend / $divisor);
    }
}