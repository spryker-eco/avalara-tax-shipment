<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipment\Business\Calculator;

use ArrayObject;
use Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer;
use Generated\Shared\Transfer\AvalaraTransactionLineTransfer;
use Generated\Shared\Transfer\AvalaraTransactionTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;
use Generated\Shared\Transfer\ShipmentGroupTransfer;
use SprykerEco\Zed\AvalaraTaxShipment\Business\Mapper\AvalaraLineItemMapper;

class MultiShipmentAbstractShipmentAvalaraTaxCalculator extends AbstractShipmentAvalaraTaxCalculator
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
        $regionZipCodeMap = $this->getRegionZipCodeMap($avalaraCreateTransactionResponseTransfer->getTransactionOrFail());
        $shipmentGroupTransfers = $this->shipmentService->groupItemsByShipment($calculableObjectTransfer->getItems());

        foreach ($shipmentGroupTransfers as $shipmentGroupTransfer) {
            if (!$shipmentGroupTransfer->getShipment() || !$shipmentGroupTransfer->getShipmentOrFail()->getMethod()) {
                continue;
            }

            $avalaraTransactionLineTransfer = $this->findAvalaraLineItemTransferForShipmentGroupTransfer(
                $shipmentGroupTransfer,
                $avalaraCreateTransactionResponseTransfer->getTransactionOrFail()->getLines(),
                $regionZipCodeMap
            );

            if (!$avalaraTransactionLineTransfer) {
                continue;
            }

            $taxRate = $this->sumTaxRateFromTransactionLineDetails($avalaraTransactionLineTransfer->getDetailsOrFail());
            $taxAmount = $this->moneyFacade->convertDecimalToInteger($avalaraTransactionLineTransfer->getTaxOrFail()->toFloat());

            if ($calculableObjectTransfer->getOriginalQuote()) {
                $this->setQuoteExpenseTax(
                    $calculableObjectTransfer->getExpenses(),
                    $shipmentGroupTransfer->getShipmentOrFail(),
                    $taxRate,
                    $taxAmount
                );
            }

            $this->setTaxRateForShipmentGroupItems($shipmentGroupTransfer, $taxRate);
        }

        return $calculableObjectTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ShipmentGroupTransfer $shipmentGroupTransfer
     * @param \ArrayObject|\Generated\Shared\Transfer\AvalaraTransactionLineTransfer[] $avalaraTransactionLineTransfers
     * @param string[] $zipCodeRegionNameMap
     *
     * @return \Generated\Shared\Transfer\AvalaraTransactionLineTransfer|null
     */
    protected function findAvalaraLineItemTransferForShipmentGroupTransfer(
        ShipmentGroupTransfer $shipmentGroupTransfer,
        ArrayObject $avalaraTransactionLineTransfers,
        array $zipCodeRegionNameMap
    ): ?AvalaraTransactionLineTransfer {
        foreach ($avalaraTransactionLineTransfers as $avalaraTransactionLineTransfer) {
            if ($avalaraTransactionLineTransfer->getRef1OrFail() !== AvalaraLineItemMapper::SHIPMENT_AVALARA_LINE_TYPE) {
                continue;
            }

            if ($shipmentGroupTransfer->getShipmentOrFail()->getMethodOrFail()->getShipmentMethodKeyOrFail() !== $avalaraTransactionLineTransfer->getRef2OrFail()) {
                continue;
            }

            $shipmentAddressZipCode = $shipmentGroupTransfer->getShipmentOrFail()->getShippingAddressOrFail()->getZipCodeOrFail();
            if (!$this->isSameRegion($zipCodeRegionNameMap, $shipmentAddressZipCode, $avalaraTransactionLineTransfer)) {
                continue;
            }

            return $avalaraTransactionLineTransfer;
        }

        return null;
    }

    /**
     * @param \Generated\Shared\Transfer\ShipmentGroupTransfer $shipmentGroupTransfer
     * @param float $taxRate
     *
     * @return void
     */
    protected function setTaxRateForShipmentGroupItems(ShipmentGroupTransfer $shipmentGroupTransfer, float $taxRate): void
    {
        $shipmentGroupTransfer->getShipmentOrFail()->getMethodOrFail()->setTaxRate($taxRate);

        foreach ($shipmentGroupTransfer->getItems() as $itemTransfer) {
            $itemTransfer->getShipmentOrFail()->getMethodOrFail()->setTaxRate($taxRate);
        }
    }

    /**
     * @param string[] $zipCodeRegionNameMap
     * @param string $itemShipmentAddressZipCode
     * @param \Generated\Shared\Transfer\AvalaraTransactionLineTransfer $avalaraTransactionLineTransfer
     *
     * @return bool
     */
    protected function isSameRegion(
        array $zipCodeRegionNameMap,
        string $itemShipmentAddressZipCode,
        AvalaraTransactionLineTransfer $avalaraTransactionLineTransfer
    ): bool {
        return isset($zipCodeRegionNameMap[$itemShipmentAddressZipCode])
            && $zipCodeRegionNameMap[$itemShipmentAddressZipCode] === $this->extractRegionNameFromAvalaraTransactionLineTransfer($avalaraTransactionLineTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\AvalaraTransactionTransfer $avalaraTransactionTransfer
     *
     * @return string[]
     */
    protected function getRegionZipCodeMap(AvalaraTransactionTransfer $avalaraTransactionTransfer): array
    {
        $zipCodeRegionMap = [];

        /** @var \Avalara\TransactionAddressModel[] $avalaraTransactionAddressModels */
        $avalaraTransactionAddressModels = $this->utilEncodingService->decodeJson($avalaraTransactionTransfer->getAddressesOrFail(), false);
        foreach ($avalaraTransactionAddressModels as $avalaraTransactionAddressModel) {
            if (array_key_exists($avalaraTransactionAddressModel->postalCode, $zipCodeRegionMap)) {
                continue;
            }

            $zipCodeRegionMap[$avalaraTransactionAddressModel->postalCode] = $avalaraTransactionAddressModel->region;
        }

        return $zipCodeRegionMap;
    }

    /**
     * @param \Generated\Shared\Transfer\AvalaraTransactionLineTransfer $avalaraTransactionLineTransfer
     *
     * @return string|null
     */
    protected function extractRegionNameFromAvalaraTransactionLineTransfer(AvalaraTransactionLineTransfer $avalaraTransactionLineTransfer): ?string
    {
        /** @var \Avalara\TransactionLineDetailModel[] $avalaraTransactionDetailModels */
        $avalaraTransactionDetailModels = $this->utilEncodingService->decodeJson($avalaraTransactionLineTransfer->getDetailsOrFail(), false);
        foreach ($avalaraTransactionDetailModels as $avalaraTransactionDetailModel) {
            if ($avalaraTransactionDetailModel->region === null) {
                continue;
            }

            return $avalaraTransactionDetailModel->region;
        }

        return null;
    }
}
