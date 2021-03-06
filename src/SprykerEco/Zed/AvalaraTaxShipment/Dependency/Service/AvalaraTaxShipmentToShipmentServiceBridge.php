<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipment\Dependency\Service;

use ArrayObject;
use Generated\Shared\Transfer\ShipmentTransfer;

class AvalaraTaxShipmentToShipmentServiceBridge implements AvalaraTaxShipmentToShipmentServiceInterface
{
    /**
     * @var \Spryker\Service\Shipment\ShipmentServiceInterface
     */
    protected $shipmentService;

    /**
     * @param \Spryker\Service\Shipment\ShipmentServiceInterface $shipmentService
     */
    public function __construct($shipmentService)
    {
        $this->shipmentService = $shipmentService;
    }

    /**
     * @param iterable<\Generated\Shared\Transfer\ItemTransfer> $itemTransferCollection
     *
     * @return \Generated\Shared\Transfer\ShipmentGroupTransfer[]|\ArrayObject
     */
    public function groupItemsByShipment(iterable $itemTransferCollection): ArrayObject
    {
        return $this->shipmentService->groupItemsByShipment($itemTransferCollection);
    }

    /**
     * @param \Generated\Shared\Transfer\ShipmentTransfer $shipmentTransfer
     *
     * @return string
     */
    public function getShipmentHashKey(ShipmentTransfer $shipmentTransfer): string
    {
        return $this->shipmentService->getShipmentHashKey($shipmentTransfer);
    }
}
