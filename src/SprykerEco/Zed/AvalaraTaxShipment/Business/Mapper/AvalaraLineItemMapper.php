<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipment\Business\Mapper;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\AvalaraAddressTransfer;
use Generated\Shared\Transfer\AvalaraLineItemTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Generated\Shared\Transfer\StockAddressTransfer;
use Generated\Shared\Transfer\StockTransfer;
use SprykerEco\Zed\AvalaraTaxShipment\Dependency\Facade\AvalaraTaxShipmentToMoneyFacadeInterface;

class AvalaraLineItemMapper implements AvalaraLineItemMapperInterface
{
    /**
     * @var string
     */
    public const SHIPMENT_AVALARA_LINE_TYPE = 'shipment';

    /**
     * @uses \Spryker\Shared\Price\PriceConfig::PRICE_MODE_GROSS
     *
     * @var string
     */
    protected const PRICE_MODE_GROSS = 'GROSS_MODE';

    /**
     * @uses \Avalara\TransactionAddressType::C_SHIPTO
     *
     * @var string
     */
    protected const AVALARA_SHIP_TO_ADDRESS_TYPE = 'ShipTo';

    /**
     * @uses \Avalara\TransactionAddressType::C_SHIPFROM
     *
     * @var string
     */
    protected const AVALARA_SHIP_FROM_ADDRESS_TYPE = 'ShipFrom';

    /**
     * @var int
     */
    protected const DEFAULT_SHIPMENT_QUANTITY = 1;

    /**
     * @var \SprykerEco\Zed\AvalaraTaxShipment\Dependency\Facade\AvalaraTaxShipmentToMoneyFacadeInterface
     */
    protected $moneyFacade;

    /**
     * @param \SprykerEco\Zed\AvalaraTaxShipment\Dependency\Facade\AvalaraTaxShipmentToMoneyFacadeInterface $moneyFacade
     */
    public function __construct(AvalaraTaxShipmentToMoneyFacadeInterface $moneyFacade)
    {
        $this->moneyFacade = $moneyFacade;
    }

    /**
     * @param \Generated\Shared\Transfer\ShipmentTransfer $shipmentTransfer
     * @param \Generated\Shared\Transfer\AvalaraLineItemTransfer $avalaraLineItemTransfer
     * @param string $priceMode
     * @param \Generated\Shared\Transfer\StockTransfer|null $stockTransfer
     *
     * @return \Generated\Shared\Transfer\AvalaraLineItemTransfer
     */
    public function mapShipmentTransferToAvalaraLineItemTransfer(
        ShipmentTransfer $shipmentTransfer,
        AvalaraLineItemTransfer $avalaraLineItemTransfer,
        string $priceMode,
        ?StockTransfer $stockTransfer
    ): AvalaraLineItemTransfer {
        $shipmentMethodTransfer = $shipmentTransfer->getMethodOrFail();
        $avalaraLineItemTransfer
            ->setTaxCode($shipmentMethodTransfer->getAvalaraTaxCode() ?? '')
            ->setQuantity(static::DEFAULT_SHIPMENT_QUANTITY)
            ->setAmount($this->moneyFacade->convertIntegerToDecimal($shipmentMethodTransfer->getStoreCurrencyPriceOrFail()))
            ->setTaxIncluded($this->isTaxIncluded($priceMode))
            ->setItemCode($shipmentMethodTransfer->getShipmentMethodKeyOrFail())
            ->setDescription($shipmentMethodTransfer->getNameOrFail())
            ->setReference1(static::SHIPMENT_AVALARA_LINE_TYPE)
            ->setReference2($shipmentMethodTransfer->getShipmentMethodKeyOrFail());

        $avalaraLineItemTransfer = $this->mapShipmentTransferShippingAddressToAvalaraLineItemTransfer($shipmentTransfer, $avalaraLineItemTransfer);

        if ($stockTransfer) {
            $this->mapStockTransferStockAddressToAvalaraLineItemTransfer($stockTransfer, $avalaraLineItemTransfer);
        }

        return $avalaraLineItemTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ShipmentTransfer $shipmentTransfer
     * @param \Generated\Shared\Transfer\AvalaraLineItemTransfer $avalaraLineItemTransfer
     *
     * @return \Generated\Shared\Transfer\AvalaraLineItemTransfer
     */
    protected function mapShipmentTransferShippingAddressToAvalaraLineItemTransfer(
        ShipmentTransfer $shipmentTransfer,
        AvalaraLineItemTransfer $avalaraLineItemTransfer
    ): AvalaraLineItemTransfer {
        if (!$shipmentTransfer->getShippingAddress()) {
            return $avalaraLineItemTransfer;
        }

        $avalaraShippingAddressTransfer = (new AvalaraAddressTransfer())->setType(static::AVALARA_SHIP_TO_ADDRESS_TYPE);
        $avalaraShippingAddressTransfer = $this->mapShipmentTransferToAvalaraAddressTransfer(
            $shipmentTransfer,
            $avalaraShippingAddressTransfer,
        );

        return $avalaraLineItemTransfer->setShippingAddress($avalaraShippingAddressTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\StockTransfer $stockTransfer
     * @param \Generated\Shared\Transfer\AvalaraLineItemTransfer $avalaraLineItemTransfer
     *
     * @return \Generated\Shared\Transfer\AvalaraLineItemTransfer
     */
    protected function mapStockTransferStockAddressToAvalaraLineItemTransfer(
        StockTransfer $stockTransfer,
        AvalaraLineItemTransfer $avalaraLineItemTransfer
    ): AvalaraLineItemTransfer {
        if (!$stockTransfer->getAddress()) {
            return $avalaraLineItemTransfer;
        }

        $avalaraShippingAddressTransfer = (new AvalaraAddressTransfer())->setType(static::AVALARA_SHIP_FROM_ADDRESS_TYPE);
        $avalaraShippingAddressTransfer = $this->mapStockAddressTransferToAvalaraAddressTransfer(
            $stockTransfer->getAddressOrFail(),
            $avalaraShippingAddressTransfer,
        );

        return $avalaraLineItemTransfer->setSourceAddress($avalaraShippingAddressTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\ShipmentTransfer $shipmentTransfer
     * @param \Generated\Shared\Transfer\AvalaraAddressTransfer $avalaraAddressTransfer
     *
     * @return \Generated\Shared\Transfer\AvalaraAddressTransfer
     */
    protected function mapShipmentTransferToAvalaraAddressTransfer(
        ShipmentTransfer $shipmentTransfer,
        AvalaraAddressTransfer $avalaraAddressTransfer
    ): AvalaraAddressTransfer {
        return $avalaraAddressTransfer->setAddress($shipmentTransfer->getShippingAddressOrFail());
    }

    /**
     * @param \Generated\Shared\Transfer\StockAddressTransfer $stockAddressTransfer
     * @param \Generated\Shared\Transfer\AvalaraAddressTransfer $avalaraAddressTransfer
     *
     * @return \Generated\Shared\Transfer\AvalaraAddressTransfer
     */
    protected function mapStockAddressTransferToAvalaraAddressTransfer(
        StockAddressTransfer $stockAddressTransfer,
        AvalaraAddressTransfer $avalaraAddressTransfer
    ): AvalaraAddressTransfer {
        $addressTransfer = (new AddressTransfer())->fromArray($stockAddressTransfer->toArray(), true);
        $addressTransfer->setIso2Code($stockAddressTransfer->getCountryOrFail()->getIso2CodeOrFail());

        return $avalaraAddressTransfer->setAddress($addressTransfer);
    }

    /**
     * @param string $priceMode
     *
     * @return bool
     */
    protected function isTaxIncluded(string $priceMode): bool
    {
        return $priceMode === static::PRICE_MODE_GROSS;
    }
}
