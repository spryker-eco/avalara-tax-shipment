<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipment\Dependency\Service;

use ArrayObject;
use Generated\Shared\Transfer\ShipmentTransfer;

interface AvalaraTaxShipmentToShipmentServiceInterface
{
    /**
     * @param iterable<\Generated\Shared\Transfer\ItemTransfer> $itemTransferCollection
     *
     * @return \Generated\Shared\Transfer\ShipmentGroupTransfer[]|\ArrayObject
     */
    public function groupItemsByShipment(iterable $itemTransferCollection): ArrayObject;

    /**
     * @param \Generated\Shared\Transfer\ShipmentTransfer $shipmentTransfer
     *
     * @return string
     */
    public function getShipmentHashKey(ShipmentTransfer $shipmentTransfer): string;
}
