<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\AvalaraTaxShipment\Business;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\DataBuilder\AvalaraCreateTransactionRequestBuilder;
use Generated\Shared\DataBuilder\AvalaraCreateTransactionResponseBuilder;
use Generated\Shared\DataBuilder\AvalaraTransactionBuilder;
use Generated\Shared\DataBuilder\AvalaraTransactionLineBuilder;
use Generated\Shared\DataBuilder\CalculableObjectBuilder;
use Generated\Shared\DataBuilder\ExpenseBuilder;
use Generated\Shared\DataBuilder\ItemBuilder;
use Generated\Shared\DataBuilder\QuoteBuilder;
use Generated\Shared\DataBuilder\ShipmentBuilder;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\AvalaraTransactionLineTransfer;
use Generated\Shared\Transfer\AvalaraTransactionTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;
use Generated\Shared\Transfer\ExpenseTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Spryker\DecimalObject\Decimal;
use SprykerEco\Zed\AvalaraTaxShipment\Business\Mapper\AvalaraLineItemMapper;

class AvalaraTaxShipmentFacadeTest extends Unit
{
    /**
     * @uses \Spryker\Shared\Price\PriceConfig::PRICE_MODE_GROSS
     *
     * @var string
     */
    protected const PRICE_MODE_GROSS = 'GROSS_MODE';

    /**
     * @uses \Spryker\Shared\Shipment\ShipmentConfig::SHIPMENT_EXPENSE_TYPE
     *
     * @var string
     */
    protected const SHIPMENT_EXPENSE_TYPE = 'SHIPMENT_EXPENSE_TYPE';

    /**
     * @var string
     */
    protected const TEST_SHIPPING_METHOD_KEY = 'test-shipping-method-key';

    /**
     * @var string
     */
    protected const TEST_ZIP_CODE_MI = '48326';

    /**
     * @var string
     */
    protected const TEST_ZIP_CODE_NY = '10001';

    /**
     * @var int
     */
    protected const TEST_SHIPMENT_METHOD_PRICE = 490;
    protected const TEST_SHIPMENT_TAX_RATE_MI = 6.0;
    protected const TEST_SHIPMENT_TAX_AMOUNT_MI = 0.28;
    protected const TEST_SHIPMENT_TAX_RATE_NY = 8.875;
    protected const TEST_SHIPMENT_TAX_AMOUNT_NY = 0.40;

    /**
     * @var string
     */
    protected const TEST_AVALARA_TAX_CODE = 'TESTCODE';

    /**
     * @var \SprykerEcoTest\Zed\AvalaraTaxShipment\AvalaraTaxShipmentBusinessTester
     */
    protected $tester;

    /**
     * @return void
     */
    public function testExpandAvalaraCreateTransactionRequestWillExpandAvalaraCreateTransactionRequestTransferWithShippingMethods(): void
    {
        // Arrange
        $shipmentTransfer = (new ShipmentBuilder())
            ->withShippingAddress([AddressTransfer::ZIP_CODE => static::TEST_ZIP_CODE_MI])
            ->withMethod([
                ShipmentMethodTransfer::SHIPMENT_METHOD_KEY => static::TEST_SHIPPING_METHOD_KEY,
                ShipmentMethodTransfer::AVALARA_TAX_CODE => static::TEST_AVALARA_TAX_CODE,
            ])
            ->build();

        $calculableObjectTransfer = $this->createCalculableObjectTransfer([
            $this->createItemTransfer($shipmentTransfer),
        ]);

        $avalaraCreateTransactionRequestTransfer = (new AvalaraCreateTransactionRequestBuilder())
            ->withTransaction()
            ->build();

        // Act
        $avalaraCreateTransactionRequestTransfer = $this->tester->getFacade()->expandAvalaraCreateTransactionWithShipment(
            $avalaraCreateTransactionRequestTransfer,
            $calculableObjectTransfer,
        );

        // Assert
        $this->assertCount(1, $avalaraCreateTransactionRequestTransfer->getTransaction()->getLines());

        /** @var \Generated\Shared\Transfer\AvalaraLineItemTransfer $avalaraLineItemTransfer */
        $avalaraLineItemTransfer = $avalaraCreateTransactionRequestTransfer->getTransaction()->getLines()->offsetGet(0);
        $this->assertSame(static::TEST_SHIPPING_METHOD_KEY, $avalaraLineItemTransfer->getItemCode());
    }

    /**
     * @return void
     */
    public function testCalculateShipmentTaxWillCalculateTaxForMultiAddressShipment(): void
    {
        // Arrange
        $shipmentTransferMi = $this->createShipmentTransfer(static::TEST_ZIP_CODE_MI);
        $shipmentTransferNy = $this->createShipmentTransfer(static::TEST_ZIP_CODE_NY);

        $expenseTransferMi = $this->createExpenseTransfer($shipmentTransferMi);
        $expenseTransferNy = $this->createExpenseTransfer($shipmentTransferNy);

        $quoteTransfer = (new QuoteBuilder())->build();

        $calculableObjectTransfer = $this->createCalculableObjectTransfer(
            [
                $this->createItemTransfer($shipmentTransferMi),
                $this->createItemTransfer($shipmentTransferNy),
            ],
            [
                $expenseTransferMi,
                $expenseTransferNy,
            ],
        )->setOriginalQuote($quoteTransfer);

        $avalaraTransactionTransferMi = (new AvalaraTransactionLineBuilder([
            AvalaraTransactionLineTransfer::QUANTITY => 1,
            AvalaraTransactionLineTransfer::ITEM_CODE => static::TEST_SHIPPING_METHOD_KEY,
            AvalaraTransactionLineTransfer::REF1 => AvalaraLineItemMapper::SHIPMENT_AVALARA_LINE_TYPE,
            AvalaraTransactionLineTransfer::REF2 => static::TEST_SHIPPING_METHOD_KEY,
            AvalaraTransactionLineTransfer::TAX => new Decimal(static::TEST_SHIPMENT_TAX_AMOUNT_MI),
            AvalaraTransactionLineTransfer::DETAILS => '[{"id":0,"transactionLineId":0,"transactionId":0,"country":"US","region":"MI","exemptAmount":0,"jurisCode":"26","jurisName":"MICHIGAN","stateAssignedNo":"","jurisType":"STA","jurisdictionType":"State","nonTaxableAmount":0,"rate":0.06,"tax":0.28,"taxableAmount":4.62,"taxType":"Sales","taxSubTypeId":"S","taxName":"MI STATE TAX","taxAuthorityTypeId":45,"taxCalculated":0.28,"rateType":"General","rateTypeCode":"G","unitOfBasis":"PerCurrencyUnit","isNonPassThru":false,"isFee":false,"reportingTaxableUnits":4.62,"reportingNonTaxableUnits":0,"reportingExemptUnits":0,"reportingTax":0.28,"reportingTaxCalculated":0.28,"liabilityType":"Seller"}]',
        ]))->build();
        $avalaraTransactionTransferNy = (new AvalaraTransactionLineBuilder([
            AvalaraTransactionLineTransfer::QUANTITY => 1,
            AvalaraTransactionLineTransfer::ITEM_CODE => static::TEST_SHIPPING_METHOD_KEY,
            AvalaraTransactionLineTransfer::REF1 => AvalaraLineItemMapper::SHIPMENT_AVALARA_LINE_TYPE,
            AvalaraTransactionLineTransfer::REF2 => static::TEST_SHIPPING_METHOD_KEY,
            AvalaraTransactionLineTransfer::TAX => new Decimal(static::TEST_SHIPMENT_TAX_AMOUNT_NY),
            AvalaraTransactionLineTransfer::DETAILS => '[{"id":0,"transactionLineId":0,"transactionId":0,"country":"US","region":"NY","exemptAmount":0,"jurisCode":"36","jurisName":"NEW YORK","stateAssignedNo":"","jurisType":"STA","jurisdictionType":"State","nonTaxableAmount":0,"rate":0.04,"tax":0.18,"taxableAmount":4.5,"taxType":"Sales","taxSubTypeId":"S","taxName":"NY STATE TAX","taxAuthorityTypeId":45,"taxCalculated":0.18,"rateType":"General","rateTypeCode":"G","unitOfBasis":"PerCurrencyUnit","isNonPassThru":false,"isFee":false,"reportingTaxableUnits":4.5,"reportingNonTaxableUnits":0,"reportingExemptUnits":0,"reportingTax":0.18,"reportingTaxCalculated":0.18,"liabilityType":"Seller"},{"id":0,"transactionLineId":0,"transactionId":0,"country":"US","region":"NY","exemptAmount":0,"jurisCode":"51000","jurisName":"NEW YORK CITY","stateAssignedNo":"NE 8081","jurisType":"CIT","jurisdictionType":"City","nonTaxableAmount":0,"rate":0.045,"tax":0.2,"taxableAmount":4.5,"taxType":"Sales","taxSubTypeId":"S","taxName":"NY CITY TAX","taxAuthorityTypeId":45,"taxCalculated":0.2,"rateType":"General","rateTypeCode":"G","unitOfBasis":"PerCurrencyUnit","isNonPassThru":false,"isFee":false,"reportingTaxableUnits":4.5,"reportingNonTaxableUnits":0,"reportingExemptUnits":0,"reportingTax":0.2,"reportingTaxCalculated":0.2,"liabilityType":"Seller"},{"id":0,"transactionLineId":0,"transactionId":0,"country":"US","region":"NY","exemptAmount":0,"jurisCode":"359071","jurisName":"METROPOLITAN COMMUTER TRANSPORTATION DISTRICT","stateAssignedNo":"NE 8081","jurisType":"STJ","jurisdictionType":"Special","nonTaxableAmount":0,"rate":0.00375,"tax":0.02,"taxableAmount":4.5,"taxType":"Sales","taxSubTypeId":"S","taxName":"NY SPECIAL TAX","taxAuthorityTypeId":45,"taxCalculated":0.02,"rateType":"General","rateTypeCode":"G","unitOfBasis":"PerCurrencyUnit","isNonPassThru":false,"isFee":false,"reportingTaxableUnits":4.5,"reportingNonTaxableUnits":0,"reportingExemptUnits":0,"reportingTax":0.02,"reportingTaxCalculated":0.02,"liabilityType":"Seller"}]',
        ]))->build();

        $avalaraTransactionTransfer = $this->createAvalaraTransactionTransfer(
            '[{"id":0,"transactionId":0,"boundaryLevel":"Address","line1":"6 Championship Dr","line2":"6","line3":"","city":"Auburn Hills","region":"MI","postalCode":"48326","country":"US","taxRegionId":4019158,"latitude":"42.696604","longitude":"-83.243744"},{"id":0,"transactionId":0,"boundaryLevel":"Zip5","line1":"4 Pennsylvania Plaza","line2":"4","line3":"","city":"New York","region":"NY","postalCode":"10001","country":"US","taxRegionId":2088629,"latitude":"40.750049","longitude":"-73.994186"}]',
            [
                $avalaraTransactionTransferMi,
                $avalaraTransactionTransferNy,
            ],
        );
        $avalaraCreateTransactionResponseTransfer = (new AvalaraCreateTransactionResponseBuilder())->build();
        $avalaraCreateTransactionResponseTransfer->setTransaction($avalaraTransactionTransfer);

        // Act
        $calculableObjectTransfer = $this->tester->getFacade()->calculateShipmentTax(
            $calculableObjectTransfer,
            $avalaraCreateTransactionResponseTransfer,
        );

        // Assert
        $resultShipmentTransferMi = $this->findShipmentTransferByZipCode(static::TEST_ZIP_CODE_MI, $calculableObjectTransfer);
        $this->assertNotNull($resultShipmentTransferMi);
        $this->assertEquals(static::TEST_SHIPMENT_TAX_RATE_MI, $resultShipmentTransferMi->getMethod()->getTaxRate());
        $resultExpenseTransferMi = $this->findExpenseTransferByShipmentTransfer($resultShipmentTransferMi, $calculableObjectTransfer->getExpenses());
        $this->assertNotNull($resultExpenseTransferMi);
        $this->assertEquals(static::TEST_SHIPMENT_TAX_RATE_MI, $resultExpenseTransferMi->getTaxRate());
        $this->assertEquals(static::TEST_SHIPMENT_TAX_AMOUNT_MI * 100.0, $resultExpenseTransferMi->getSumTaxAmount());

        $resultShipmentTransferNy = $this->findShipmentTransferByZipCode(static::TEST_ZIP_CODE_NY, $calculableObjectTransfer);
        $this->assertNotNull($resultShipmentTransferNy);
        $this->assertEquals(static::TEST_SHIPMENT_TAX_RATE_NY, $resultShipmentTransferNy->getMethod()->getTaxRate());
        $resultExpenseTransferNy = $this->findExpenseTransferByShipmentTransfer($resultShipmentTransferNy, $calculableObjectTransfer->getExpenses());
        $this->assertNotNull($resultExpenseTransferNy);
        $this->assertEquals(static::TEST_SHIPMENT_TAX_RATE_NY, $resultExpenseTransferNy->getTaxRate());
        $this->assertEquals(static::TEST_SHIPMENT_TAX_AMOUNT_NY * 100.0, $resultExpenseTransferNy->getSumTaxAmount());
    }

    /**
     * @return void
     */
    public function testCalculateShipmentTaxWillCalculateTaxForSingleAddressShipment(): void
    {
        // Arrange
        $shipmentTransfer = $this->createShipmentTransfer(static::TEST_ZIP_CODE_MI);
        $expenseTransfer = $this->createExpenseTransfer($shipmentTransfer);
        $quoteTransfer = (new QuoteBuilder())->build();

        $calculableObjectTransfer = $this->createCalculableObjectTransfer(
            [
                $this->createItemTransfer(),
                $this->createItemTransfer(),
            ],
            [
                $expenseTransfer,
            ],
        )
            ->setShipment($shipmentTransfer)
            ->setOriginalQuote($quoteTransfer);

        $avalaraTransactionTransfer1 = (new AvalaraTransactionLineBuilder([
            AvalaraTransactionLineTransfer::QUANTITY => 1,
            AvalaraTransactionLineTransfer::ITEM_CODE => static::TEST_SHIPPING_METHOD_KEY,
            AvalaraTransactionLineTransfer::REF1 => AvalaraLineItemMapper::SHIPMENT_AVALARA_LINE_TYPE,
            AvalaraTransactionLineTransfer::REF2 => static::TEST_SHIPPING_METHOD_KEY,
            AvalaraTransactionLineTransfer::TAX => new Decimal(static::TEST_SHIPMENT_TAX_AMOUNT_MI),
            AvalaraTransactionLineTransfer::DETAILS => '[{"id":0,"transactionLineId":0,"transactionId":0,"country":"US","region":"MI","exemptAmount":0,"jurisCode":"26","jurisName":"MICHIGAN","stateAssignedNo":"","jurisType":"STA","jurisdictionType":"State","nonTaxableAmount":0,"rate":0.06,"tax":0.28,"taxableAmount":4.62,"taxType":"Sales","taxSubTypeId":"S","taxName":"MI STATE TAX","taxAuthorityTypeId":45,"taxCalculated":0.28,"rateType":"General","rateTypeCode":"G","unitOfBasis":"PerCurrencyUnit","isNonPassThru":false,"isFee":false,"reportingTaxableUnits":4.62,"reportingNonTaxableUnits":0,"reportingExemptUnits":0,"reportingTax":0.28,"reportingTaxCalculated":0.28,"liabilityType":"Seller"}]',
        ]))->build();
        $avalaraTransactionTransfer2 = (new AvalaraTransactionLineBuilder([
            AvalaraTransactionLineTransfer::QUANTITY => 1,
            AvalaraTransactionLineTransfer::ITEM_CODE => static::TEST_SHIPPING_METHOD_KEY,
            AvalaraTransactionLineTransfer::REF1 => AvalaraLineItemMapper::SHIPMENT_AVALARA_LINE_TYPE,
            AvalaraTransactionLineTransfer::REF2 => static::TEST_SHIPPING_METHOD_KEY,
            AvalaraTransactionLineTransfer::TAX => new Decimal(static::TEST_SHIPMENT_TAX_AMOUNT_MI),
            AvalaraTransactionLineTransfer::DETAILS => '[{"id":0,"transactionLineId":0,"transactionId":0,"country":"US","region":"MI","exemptAmount":0,"jurisCode":"26","jurisName":"MICHIGAN","stateAssignedNo":"","jurisType":"STA","jurisdictionType":"State","nonTaxableAmount":0,"rate":0.06,"tax":0.28,"taxableAmount":4.62,"taxType":"Sales","taxSubTypeId":"S","taxName":"MI STATE TAX","taxAuthorityTypeId":45,"taxCalculated":0.28,"rateType":"General","rateTypeCode":"G","unitOfBasis":"PerCurrencyUnit","isNonPassThru":false,"isFee":false,"reportingTaxableUnits":4.62,"reportingNonTaxableUnits":0,"reportingExemptUnits":0,"reportingTax":0.28,"reportingTaxCalculated":0.28,"liabilityType":"Seller"}]',
        ]))->build();

        $avalaraTransactionTransfer = $this->createAvalaraTransactionTransfer(
            '[{"id":0,"transactionId":0,"boundaryLevel":"Zip5","line1":"Seeburger Str., 270, Block B","line2":"270","line3":"Block B","city":"Auburn Hills","region":"MI","postalCode":"48326","country":"US","taxRegionId":4019158,"latitude":"42.666113","longitude":"-83.243875"}]',
            [
                $avalaraTransactionTransfer1,
                $avalaraTransactionTransfer2,
            ],
        );
        $avalaraCreateTransactionResponseTransfer = (new AvalaraCreateTransactionResponseBuilder())->build();
        $avalaraCreateTransactionResponseTransfer->setTransaction($avalaraTransactionTransfer);

        // Act
        $calculableObjectTransfer = $this->tester->getFacade()->calculateShipmentTax(
            $calculableObjectTransfer,
            $avalaraCreateTransactionResponseTransfer,
        );

        // Assert
        $resultShipmentTransfer = $calculableObjectTransfer->getShipment();
        $this->assertNotNull($resultShipmentTransfer);
        $this->assertEquals(static::TEST_SHIPMENT_TAX_RATE_MI, $resultShipmentTransfer->getMethod()->getTaxRate());

        foreach ($calculableObjectTransfer->getExpenses() as $expenseTransfer) {
            $this->assertEquals(static::TEST_SHIPMENT_TAX_RATE_MI, $expenseTransfer->getTaxRate());
            $this->assertEquals(static::TEST_SHIPMENT_TAX_AMOUNT_MI * 100.0, $expenseTransfer->getSumTaxAmount());
        }
    }

    /**
     * @param string $zipCode
     *
     * @return \Generated\Shared\Transfer\ShipmentTransfer
     */
    protected function createShipmentTransfer(string $zipCode): ShipmentTransfer
    {
        return (new ShipmentBuilder())
            ->withShippingAddress([AddressTransfer::ZIP_CODE => $zipCode])
            ->withMethod([
                ShipmentMethodTransfer::SHIPMENT_METHOD_KEY => static::TEST_SHIPPING_METHOD_KEY,
                ShipmentMethodTransfer::STORE_CURRENCY_PRICE => static::TEST_SHIPMENT_METHOD_PRICE,
            ])
            ->build();
    }

    /**
     * @param \Generated\Shared\Transfer\ShipmentTransfer|null $shipmentTransfer
     *
     * @return \Generated\Shared\Transfer\ItemTransfer
     */
    protected function createItemTransfer(?ShipmentTransfer $shipmentTransfer = null): ItemTransfer
    {
        return (new ItemBuilder())
            ->build()
            ->setShipment($shipmentTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\ShipmentTransfer $shipmentTransfer
     *
     * @return \Generated\Shared\Transfer\ExpenseTransfer|\Spryker\Shared\Kernel\Transfer\AbstractTransfer
     */
    protected function createExpenseTransfer(ShipmentTransfer $shipmentTransfer)
    {
        return (new ExpenseBuilder([
            ExpenseTransfer::SHIPMENT => $shipmentTransfer,
            ExpenseTransfer::TYPE => static::SHIPMENT_EXPENSE_TYPE,
        ]))->build();
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     * @param array<\Generated\Shared\Transfer\ExpenseTransfer> $expenseTransfers
     *
     * @return \Generated\Shared\Transfer\CalculableObjectTransfer
     */
    protected function createCalculableObjectTransfer(array $itemTransfers = [], array $expenseTransfers = []): CalculableObjectTransfer
    {
        $calculableObjectTransfer = (new CalculableObjectBuilder([
            CalculableObjectTransfer::PRICE_MODE => static::PRICE_MODE_GROSS,
        ]))->build();

        return $calculableObjectTransfer
            ->setItems(new ArrayObject($itemTransfers))
            ->setExpenses(new ArrayObject($expenseTransfers));
    }

    /**
     * @param string $addresses
     * @param array<\Generated\Shared\Transfer\AvalaraTransactionLineTransfer> $avalaraTransactionLineTransfers
     *
     * @return \Generated\Shared\Transfer\AvalaraTransactionTransfer
     */
    protected function createAvalaraTransactionTransfer(string $addresses, array $avalaraTransactionLineTransfers): AvalaraTransactionTransfer
    {
        return (new AvalaraTransactionBuilder([AvalaraTransactionTransfer::ADDRESSES => $addresses]))
            ->build()
            ->setLines(new ArrayObject($avalaraTransactionLineTransfers));
    }

    /**
     * @param string $zipCode
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return \Generated\Shared\Transfer\ShipmentTransfer|null
     */
    protected function findShipmentTransferByZipCode(string $zipCode, CalculableObjectTransfer $calculableObjectTransfer): ?ShipmentTransfer
    {
        foreach ($calculableObjectTransfer->getItems() as $itemTransfer) {
            if (
                $itemTransfer->getShipment()
                && $itemTransfer->getShipmentOrFail()->getShippingAddress()
                && $itemTransfer->getShipmentOrFail()->getShippingAddressOrFail()->getZipCode() === $zipCode
            ) {
                return $itemTransfer->getShipmentOrFail();
            }
        }

        return null;
    }

    /**
     * @param \Generated\Shared\Transfer\ShipmentTransfer $shipmentTransfer
     * @param \ArrayObject $expenseTransfers
     *
     * @return \Generated\Shared\Transfer\ExpenseTransfer|null
     */
    protected function findExpenseTransferByShipmentTransfer(ShipmentTransfer $shipmentTransfer, ArrayObject $expenseTransfers): ?ExpenseTransfer
    {
        $itemShipmentKey = $this->getShipmentHashKey($shipmentTransfer);
        foreach ($expenseTransfers as $expenseTransfer) {
            if (!$expenseTransfer->getShipment()) {
                continue;
            }

            $expenseShipmentKey = $this->getShipmentHashKey($expenseTransfer->getShipment());

            if ($expenseShipmentKey === $itemShipmentKey && $expenseTransfer->getType() === static::SHIPMENT_EXPENSE_TYPE) {
                return $expenseTransfer;
            }
        }

        return null;
    }

    /**
     * @param \Generated\Shared\Transfer\ShipmentTransfer $shipmentTransfer
     *
     * @return string
     */
    protected function getShipmentHashKey(ShipmentTransfer $shipmentTransfer): string
    {
        return $this->tester->getLocator()->shipment()->service()->getShipmentHashKey($shipmentTransfer);
    }
}
