<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipment\Business;

use Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer;
use Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;

interface AvalaraTaxShipmentFacadeInterface
{
    /**
     * Specification:
     * - Expands `AvalaraCreateTransactionRequestTransfer` with shipments.
     * - Requires `CalculableObjectTransfer.priceMode` and `AvalaraCreateTransactionRequestTransfer.transaction` to be set.
     * - Expects `CalculableObjectTransfer.items.shipment.method.avalaraTaxCode` to be set.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return \Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer
     */
    public function expandAvalaraCreateTransactionWithShipment(
        AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer,
        CalculableObjectTransfer $calculableObjectTransfer
    ): AvalaraCreateTransactionRequestTransfer;

    /**
     * Specification:
     * - Calculates taxes for shipment methods based on `AvalaraCreateTransactionResponseTransfer`.
     * - Sets tax rate to `CalculableObjectTransfer.items.shipment.method.taxRate`.
     * - Sets tax rate and tax amount to `CalculableObjectTransfer.originalQuote.expenses` according to shipment method.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     * @param \Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer $avalaraCreateTransactionResponseTransfer
     *
     * @return \Generated\Shared\Transfer\CalculableObjectTransfer
     */
    public function calculateShipmentTax(
        CalculableObjectTransfer $calculableObjectTransfer,
        AvalaraCreateTransactionResponseTransfer $avalaraCreateTransactionResponseTransfer
    ): CalculableObjectTransfer;
}
