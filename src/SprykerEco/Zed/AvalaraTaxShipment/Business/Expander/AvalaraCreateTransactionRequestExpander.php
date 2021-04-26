<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipment\Business\Expander;

use Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer;
use Generated\Shared\Transfer\AvalaraLineItemTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use SprykerEco\Zed\AvalaraTaxShipment\Business\Mapper\AvalaraLineItemMapperInterface;

class AvalaraCreateTransactionRequestExpander implements AvalaraCreateTransactionRequestExpanderInterface
{
    /**
     * @var \SprykerEco\Zed\AvalaraTaxShipment\Business\Mapper\AvalaraLineItemMapperInterface
     */
    protected $avalaraLineItemMapper;

    /**
     * @param \SprykerEco\Zed\AvalaraTaxShipment\Business\Mapper\AvalaraLineItemMapperInterface $avalaraLineItemMapper
     */
    public function __construct(AvalaraLineItemMapperInterface $avalaraLineItemMapper)
    {
        $this->avalaraLineItemMapper = $avalaraLineItemMapper;
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
        if ($this->isMultiAddressShipment($calculableObjectTransfer)) {
            return $this->expandAvalaraCreateTransactionWithItemLevelShipment(
                $avalaraCreateTransactionRequestTransfer,
                $calculableObjectTransfer
            );
        }

        return $this->expandAvalaraCreateTransactionWithQuoteLevelShipment(
            $avalaraCreateTransactionRequestTransfer,
            $calculableObjectTransfer
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
        foreach ($calculableObjectTransfer->getItems() as $itemTransfer) {
            if (!$this->isItemHasShipmentMethod($itemTransfer)) {
                continue;
            }

            $avalaraLineItemTransfer = $this->avalaraLineItemMapper->mapShipmentTransferToAvalaraLineItemTransfer(
                $itemTransfer->getShipmentOrFail(),
                new AvalaraLineItemTransfer(),
                $calculableObjectTransfer->getPriceModeOrFail()
            );

            $avalaraCreateTransactionRequestTransfer->getTransactionOrFail()->addLine($avalaraLineItemTransfer);
        }

        return $avalaraCreateTransactionRequestTransfer;
    }

    /**
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
            $calculableObjectTransfer->getPriceModeOrFail()
        );

        $avalaraCreateTransactionRequestTransfer->getTransactionOrFail()->addLine($avalaraLineItemTransfer);

        return $avalaraCreateTransactionRequestTransfer;
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
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return bool
     */
    protected function isItemHasShipmentMethod(ItemTransfer $itemTransfer): bool
    {
        return $itemTransfer->getShipment() && $itemTransfer->getShipmentOrFail()->getMethod();
    }
}
