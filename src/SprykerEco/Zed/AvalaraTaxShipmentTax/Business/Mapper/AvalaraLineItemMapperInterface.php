<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Mapper;

use Generated\Shared\Transfer\AvalaraLineItemTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;

interface AvalaraLineItemMapperInterface
{
    /**
     * @param \Generated\Shared\Transfer\ShipmentTransfer $shipmentTransfer
     * @param \Generated\Shared\Transfer\AvalaraLineItemTransfer $avalaraLineItemTransfer
     * @param string $priceMode
     *
     * @return \Generated\Shared\Transfer\AvalaraLineItemTransfer
     */
    public function mapShipmentTransferToAvalaraLineItemTransfer(
        ShipmentTransfer $shipmentTransfer,
        AvalaraLineItemTransfer $avalaraLineItemTransfer,
        string $priceMode
    ): AvalaraLineItemTransfer;
}
