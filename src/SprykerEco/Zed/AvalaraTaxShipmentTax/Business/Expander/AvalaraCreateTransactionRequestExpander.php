<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Expander;

use Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer;
use Generated\Shared\Transfer\AvalaraLineItemTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Mapper\AvalaraLineItemMapperInterface;

class AvalaraCreateTransactionRequestExpander implements AvalaraCreateTransactionRequestExpanderInterface
{
    /**
     * @var \SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Mapper\AvalaraLineItemMapperInterface
     */
    protected $avalaraItemLineMapper;

    /**
     * @param \SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Mapper\AvalaraLineItemMapperInterface $avalaraItemLineMapper
     */
    public function __construct(AvalaraLineItemMapperInterface $avalaraItemLineMapper)
    {
        $this->avalaraItemLineMapper = $avalaraItemLineMapper;
    }

    /**
     * @param \Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return \Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer
     */
    public function expandAvalaraCreateTransactionRequest(
        AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer,
        CalculableObjectTransfer $calculableObjectTransfer
    ): AvalaraCreateTransactionRequestTransfer {
        foreach ($calculableObjectTransfer->getItems() as $itemTransfer) {
            if (!$this->isItemHasShipmentMethod($itemTransfer)) {
                continue;
            }

            $avalaraLineItemTransfer = $this->avalaraItemLineMapper->mapShipmentTransferToAvalaraLineItemTransfer(
                $itemTransfer->getShipmentOrFail(),
                new AvalaraLineItemTransfer(),
                $calculableObjectTransfer->getPriceModeOrFail()
            );

            $avalaraCreateTransactionRequestTransfer->getTransactionOrFail()->addLine($avalaraLineItemTransfer);
        }

        return $avalaraCreateTransactionRequestTransfer;
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
