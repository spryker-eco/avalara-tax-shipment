<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Calculator;

use ArrayObject;
use Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;
use Generated\Shared\Transfer\ExpenseTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Mapper\AvalaraLineItemMapper;
use SprykerEco\Zed\AvalaraTaxShipmentTax\Dependency\Facade\AvalaraTaxShipmentTaxToMoneyFacadeInterface;
use SprykerEco\Zed\AvalaraTaxShipmentTax\Dependency\Service\AvalaraTaxShipmentTaxToShipmentServiceInterface;
use SprykerEco\Zed\AvalaraTaxShipmentTax\Dependency\Service\AvalaraTaxShipmentTaxToUtilEncodingServiceInterface;

abstract class AbstractShipmentAvalaraTaxCalculator implements ShipmentAvalaraTaxCalculatorInterface
{
    /**
     * @uses \Spryker\Shared\Shipment\ShipmentConfig::SHIPMENT_EXPENSE_TYPE
     */
    protected const SHIPMENT_EXPENSE_TYPE = 'SHIPMENT_EXPENSE_TYPE';

    /**
     * @var \SprykerEco\Zed\AvalaraTaxShipmentTax\Dependency\Facade\AvalaraTaxShipmentTaxToMoneyFacadeInterface
     */
    protected $moneyFacade;

    /**
     * @var \SprykerEco\Zed\AvalaraTaxShipmentTax\Dependency\Service\AvalaraTaxShipmentTaxToShipmentServiceInterface
     */
    protected $shipmentService;

    /**
     * @var \SprykerEco\Zed\AvalaraTaxShipmentTax\Dependency\Service\AvalaraTaxShipmentTaxToUtilEncodingServiceInterface
     */
    protected $utilEncodingService;

    /**
     * @param \SprykerEco\Zed\AvalaraTaxShipmentTax\Dependency\Facade\AvalaraTaxShipmentTaxToMoneyFacadeInterface $moneyFacade
     * @param \SprykerEco\Zed\AvalaraTaxShipmentTax\Dependency\Service\AvalaraTaxShipmentTaxToShipmentServiceInterface $shipmentService
     * @param \SprykerEco\Zed\AvalaraTaxShipmentTax\Dependency\Service\AvalaraTaxShipmentTaxToUtilEncodingServiceInterface $utilEncodingService
     */
    public function __construct(
        AvalaraTaxShipmentTaxToMoneyFacadeInterface $moneyFacade,
        AvalaraTaxShipmentTaxToShipmentServiceInterface $shipmentService,
        AvalaraTaxShipmentTaxToUtilEncodingServiceInterface $utilEncodingService
    ) {
        $this->moneyFacade = $moneyFacade;
        $this->shipmentService = $shipmentService;
        $this->utilEncodingService = $utilEncodingService;
    }

    /**
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     * @param \Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer $avalaraCreateTransactionResponseTransfer
     *
     * @return \Generated\Shared\Transfer\CalculableObjectTransfer
     */
    abstract public function calculateTax(
        CalculableObjectTransfer $calculableObjectTransfer,
        AvalaraCreateTransactionResponseTransfer $avalaraCreateTransactionResponseTransfer
    ): CalculableObjectTransfer;

    /**
     * @param \ArrayObject|\Generated\Shared\Transfer\AvalaraTransactionLineTransfer[] $avalaraTransactionLineTransfers
     *
     * @return \Generated\Shared\Transfer\AvalaraTransactionLineTransfer[][]
     */
    protected function getProductOptionAvalaraTransactionLineTransfersIndexedByItemSkuAndProductOptionSku(ArrayObject $avalaraTransactionLineTransfers): array
    {
        $mappedProductOptionAvalaraTransactionLineTransfers = [];
        foreach ($avalaraTransactionLineTransfers as $avalaraTransactionLineTransfer) {
            if ($avalaraTransactionLineTransfer->getRef1OrFail() !== AvalaraLineItemMapper::SHIPMENT_AVALARA_LINE_TYPE) {
                continue;
            }

            $itemSku = $avalaraTransactionLineTransfer->getRef2OrFail();
            $productOptionSku = $avalaraTransactionLineTransfer->getItemCodeOrFail();
            $mappedProductOptionAvalaraTransactionLineTransfers[$itemSku][$productOptionSku] = $avalaraTransactionLineTransfer;
        }

        return $mappedProductOptionAvalaraTransactionLineTransfers;
    }

    /**
     * @param string $transactionLineDetails
     *
     * @return float
     */
    protected function sumTaxRateFromTransactionLineDetails(string $transactionLineDetails): float
    {
        $taxRateSum = 0.0;

        /** @var \Avalara\TransactionLineDetailModel[] $transactionLineDetailModels */
        $transactionLineDetailModels = $this->utilEncodingService->decodeJson($transactionLineDetails, false);
        foreach ($transactionLineDetailModels as $transactionLineDetailModel) {
            $taxRateSum += $transactionLineDetailModel->rate ?? 0.0;
        }

        return $this->convertToPercents($taxRateSum);
    }

    /**
     * @param float $number
     *
     * @return float
     */
    protected function convertToPercents(float $number): float
    {
        return $number * 100.0;
    }

    /**
     * @param \ArrayObject|\Generated\Shared\Transfer\ExpenseTransfer[] $expenseTransfers
     * @param \Generated\Shared\Transfer\ShipmentTransfer $shipmentTransfer
     *
     * @return \Generated\Shared\Transfer\ExpenseTransfer|null
     */
    protected function findQuoteExpenseByShipment(
        ArrayObject $expenseTransfers,
        ShipmentTransfer $shipmentTransfer
    ): ?ExpenseTransfer {
        $itemShipmentKey = $this->shipmentService->getShipmentHashKey($shipmentTransfer);
        foreach ($expenseTransfers as $expenseTransfer) {
            if (!$expenseTransfer->getShipment()) {
                continue;
            }

            $expenseShipmentKey = $this->shipmentService->getShipmentHashKey($expenseTransfer->getShipmentOrFail());

            if ($expenseShipmentKey === $itemShipmentKey && $expenseTransfer->getType() === static::SHIPMENT_EXPENSE_TYPE) {
                return $expenseTransfer;
            }
        }

        return null;
    }

    /**
     * @param \ArrayObject|\Generated\Shared\Transfer\ExpenseTransfer[] $expenseTransfers
     * @param \Generated\Shared\Transfer\ShipmentTransfer $shipmentTransfer
     * @param float $taxRate
     * @param int $taxAmount
     *
     * @return void
     */
    protected function setQuoteExpenseTax(ArrayObject $expenseTransfers, ShipmentTransfer $shipmentTransfer, float $taxRate, int $taxAmount): void
    {
        $expenseTransfer = $this->findQuoteExpenseByShipment($expenseTransfers, $shipmentTransfer);

        if (!$expenseTransfer) {
            return;
        }

        $expenseTransfer->setTaxRate($taxRate);
        $expenseTransfer->setSumTaxAmount($taxAmount);
    }
}
