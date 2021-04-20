<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Calculator;

use Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;
use SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Mapper\AvalaraLineItemMapper;

class SingleAddressShipmentAvalaraTaxCalculator extends AbstractShipmentAvalaraTaxCalculator
{
    /**
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     * @param \Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer $avalaraCreateTransactionResponseTransfer
     *
     * @return \Generated\Shared\Transfer\CalculableObjectTransfer
     */
    public function calculateTax(
        CalculableObjectTransfer $calculableObjectTransfer,
        AvalaraCreateTransactionResponseTransfer $avalaraCreateTransactionResponseTransfer
    ): CalculableObjectTransfer {
        $shipmentTransfer = $calculableObjectTransfer->getShipmentOrFail();

        foreach ($avalaraCreateTransactionResponseTransfer->getTransactionOrFail()->getLines() as $avalaraTransactionLineTransfer) {
            if ($avalaraTransactionLineTransfer->getRef1OrFail() !== AvalaraLineItemMapper::SHIPMENT_AVALARA_LINE_TYPE) {
                continue;
            }

            $taxRate = $this->sumTaxRateFromTransactionLineDetails($avalaraTransactionLineTransfer->getDetailsOrFail());
            $taxAmount = $this->moneyFacade->convertDecimalToInteger($avalaraTransactionLineTransfer->getTaxOrFail()->toFloat());

            if ($calculableObjectTransfer->getOriginalQuote()) {
                $this->setQuoteExpenseTax(
                    $calculableObjectTransfer->getOriginalQuoteOrFail()->getExpenses(),
                    $shipmentTransfer,
                    $taxRate,
                    $taxAmount
                );
            }

            $shipmentTransfer->getMethodOrFail()->setTaxRate($taxRate);
        }

        return $calculableObjectTransfer;
    }
}
