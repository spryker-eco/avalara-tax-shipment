<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipment\Business\Expander;

use ArrayObject;
use Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer;
use Generated\Shared\Transfer\AvalaraLineItemTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\StockTransfer;
use SprykerEco\Zed\AvalaraTaxShipment\Business\Mapper\AvalaraLineItemMapperInterface;
use SprykerEco\Zed\AvalaraTaxShipment\Dependency\Service\AvalaraTaxShipmentToShipmentServiceInterface;

class AvalaraCreateTransactionRequestExpander implements AvalaraCreateTransactionRequestExpanderInterface
{
    /**
     * @var \SprykerEco\Zed\AvalaraTaxShipment\Business\Mapper\AvalaraLineItemMapperInterface
     */
    protected $avalaraLineItemMapper;

    /**
     * @var \SprykerEco\Zed\AvalaraTaxShipment\Dependency\Service\AvalaraTaxShipmentToShipmentServiceInterface
     */
    protected $shipmentService;

    /**
     * @param \SprykerEco\Zed\AvalaraTaxShipment\Business\Mapper\AvalaraLineItemMapperInterface $avalaraLineItemMapper
     * @param \SprykerEco\Zed\AvalaraTaxShipment\Dependency\Service\AvalaraTaxShipmentToShipmentServiceInterface $shipmentService
     */
    public function __construct(
        AvalaraLineItemMapperInterface $avalaraLineItemMapper,
        AvalaraTaxShipmentToShipmentServiceInterface $shipmentService
    ) {
        $this->avalaraLineItemMapper = $avalaraLineItemMapper;
        $this->shipmentService = $shipmentService;
    }

    /**
     * @param \Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return \Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer
     */
    public function expandAvalaraCreateTransactionWithShipment(
        AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer,
        CalculableObjectTransfer $calculableObjectTransfer
    ): AvalaraCreateTransactionRequestTransfer {
        if (!$this->isShipmentMethodSelected($calculableObjectTransfer)) {
            return $avalaraCreateTransactionRequestTransfer;
        }

        if ($this->isMultiAddressShipment($calculableObjectTransfer)) {
            return $this->expandAvalaraCreateTransactionWithItemLevelShipment(
                $avalaraCreateTransactionRequestTransfer,
                $calculableObjectTransfer,
            );
        }

        return $this->expandAvalaraCreateTransactionWithQuoteLevelShipment(
            $avalaraCreateTransactionRequestTransfer,
            $calculableObjectTransfer,
        );
    }

    /**
     * @param \Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return \Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer
     */
    protected function expandAvalaraCreateTransactionWithItemLevelShipment(
        AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer,
        CalculableObjectTransfer $calculableObjectTransfer
    ): AvalaraCreateTransactionRequestTransfer {
        $shipmentGroupTransfers = $this->shipmentService->groupItemsByShipment($calculableObjectTransfer->getItems());

        foreach ($shipmentGroupTransfers as $shipmentGroupTransfer) {
            $avalaraLineItemTransfer = $this->avalaraLineItemMapper->mapShipmentTransferToAvalaraLineItemTransfer(
                $shipmentGroupTransfer->getShipmentOrFail(),
                new AvalaraLineItemTransfer(),
                $calculableObjectTransfer->getPriceModeOrFail(),
                $this->findExclusiveStockForItems($shipmentGroupTransfer->getItems()),
            );

            $avalaraCreateTransactionRequestTransfer->getTransactionOrFail()->addLine($avalaraLineItemTransfer);
        }

        return $avalaraCreateTransactionRequestTransfer;
    }

    /**
     * @deprecated Exists for Backward Compatibility reasons only.
     *
     * @param \Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return \Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer
     */
    protected function expandAvalaraCreateTransactionWithQuoteLevelShipment(
        AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer,
        CalculableObjectTransfer $calculableObjectTransfer
    ): AvalaraCreateTransactionRequestTransfer {
        $avalaraLineItemTransfer = $this->avalaraLineItemMapper->mapShipmentTransferToAvalaraLineItemTransfer(
            $calculableObjectTransfer->getShipmentOrFail(),
            new AvalaraLineItemTransfer(),
            $calculableObjectTransfer->getPriceModeOrFail(),
            $this->findExclusiveStockForItems($calculableObjectTransfer->getItems()),
        );

        $avalaraCreateTransactionRequestTransfer->getTransactionOrFail()->addLine($avalaraLineItemTransfer);

        return $avalaraCreateTransactionRequestTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer[]|\ArrayObject $itemTransfers
     *
     * @return \Generated\Shared\Transfer\StockTransfer|null
     */
    protected function findExclusiveStockForItems(ArrayObject $itemTransfers): ?StockTransfer
    {
        $stockTransfer = null;

        foreach ($itemTransfers as $itemTransfer) {
            if ($itemTransfer->getWarehouse() === null) {
                return null;
            }

            if ($stockTransfer && $stockTransfer->getNameOrFail() !== $itemTransfer->getWarehouseOrFail()->getNameOrFail()) {
                return null;
            }

            $stockTransfer = $itemTransfer->getWarehouseOrFail();
        }

        return $stockTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return bool
     */
    protected function isShipmentMethodSelected(CalculableObjectTransfer $calculableObjectTransfer): bool
    {
        if ($this->isQuoteHasShipmentMethod($calculableObjectTransfer)) {
            return true;
        }

        foreach ($calculableObjectTransfer->getItems() as $itemTransfer) {
            if (!$this->isItemHasShipmentMethod($itemTransfer)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return bool
     */
    protected function isMultiAddressShipment(CalculableObjectTransfer $calculableObjectTransfer): bool
    {
        foreach ($calculableObjectTransfer->getItems() as $itemTransfer) {
            if (!$this->isItemHasShipmentMethod($itemTransfer)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @deprecated Exists for Backward Compatibility reasons only.
     *
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return bool
     */
    protected function isQuoteHasShipmentMethod(CalculableObjectTransfer $calculableObjectTransfer): bool
    {
        return $calculableObjectTransfer->getShipment()
            && $calculableObjectTransfer->getShipmentOrFail()->getMethod()
            && $calculableObjectTransfer->getShipmentOrFail()->getMethodOrFail()->getStoreCurrencyPrice();
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return bool
     */
    protected function isItemHasShipmentMethod(ItemTransfer $itemTransfer): bool
    {
        return $itemTransfer->getShipment()
            && $itemTransfer->getShipmentOrFail()->getMethod()
            && $itemTransfer->getShipmentOrFail()->getMethodOrFail()->getStoreCurrencyPrice();
    }
}
