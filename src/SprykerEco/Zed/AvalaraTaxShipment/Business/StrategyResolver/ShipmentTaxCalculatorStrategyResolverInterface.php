<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipment\Business\StrategyResolver;

use Generated\Shared\Transfer\CalculableObjectTransfer;
use SprykerEco\Zed\AvalaraTaxShipment\Business\Calculator\ShipmentAvalaraTaxCalculatorInterface;

/**
 * @deprecated Exists for Backward Compatibility reasons only.
 */
interface ShipmentTaxCalculatorStrategyResolverInterface
{
    /**
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return \SprykerEco\Zed\AvalaraTaxShipment\Business\Calculator\ShipmentAvalaraTaxCalculatorInterface
     */
    public function resolve(CalculableObjectTransfer $calculableObjectTransfer): ShipmentAvalaraTaxCalculatorInterface;
}
