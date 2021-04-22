<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipment\Business\Calculator;

use ArrayObject;
use Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;
use Generated\Shared\Transfer\ExpenseTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use SprykerEco\Zed\AvalaraTaxShipment\Business\Mapper\AvalaraLineItemMapper;
use SprykerEco\Zed\AvalaraTaxShipment\Dependency\Facade\AvalaraTaxShipmentToMoneyFacadeInterface;
use SprykerEco\Zed\AvalaraTaxShipment\Dependency\Service\AvalaraTaxShipmentToShipmentServiceInterface;
use SprykerEco\Zed\AvalaraTaxShipment\Dependency\Service\AvalaraTaxShipmentToUtilEncodingServiceInterface;

abstract class AbstractShipmentAvalaraTaxCalculator implements ShipmentAvalaraTaxCalculatorInterface
{
    /**
     * @uses \Spryker\Shared\Shipment\ShipmentConfig::SHIPMENT_EXPENSE_TYPE
     */
    protected const SHIPMENT_EXPENSE_TYPE = 'SHIPMENT_EXPENSE_TYPE';

    /**
     * @var \SprykerEco\Zed\AvalaraTaxShipment\Dependency\Facade\AvalaraTaxShipmentToMoneyFacadeInterface
     */
    protected $moneyFacade;

    /**
     * @var \SprykerEco\Zed\AvalaraTaxShipment\Dependency\Service\AvalaraTaxShipmentToShipmentServiceInterface
     */
    protected $shipmentService;

    /**
     * @var \SprykerEco\Zed\AvalaraTaxShipment\Dependency\Service\AvalaraTaxShipmentToUtilEncodingServiceInterface
     */
    protected $utilEncodingService;

    /**
     * @param \SprykerEco\Zed\AvalaraTaxShipment\Dependency\Facade\AvalaraTaxShipmentToMoneyFacadeInterface $moneyFacade
     * @param \SprykerEco\Zed\AvalaraTaxShipment\Dependency\Service\AvalaraTaxShipmentToShipmentServiceInterface $shipmentService
     * @param \SprykerEco\Zed\AvalaraTaxShipment\Dependency\Service\AvalaraTaxShipmentToUtilEncodingServiceInterface $utilEncodingService
     */
    public function __construct(
        AvalaraTaxShipmentToMoneyFacadeInterface $moneyFacade,
        AvalaraTaxShipmentToShipmentServiceInterface $shipmentService,
        AvalaraTaxShipmentToUtilEncodingServiceInterface $utilEncodingService
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
            if (!$expenseTransfer->getShipment() || $expenseTransfer->getType() !== static::SHIPMENT_EXPENSE_TYPE) {
                continue;
            }

            $expenseShipmentKey = $this->shipmentService->getShipmentHashKey($expenseTransfer->getShipmentOrFail());

            if ($expenseShipmentKey === $itemShipmentKey) {
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
