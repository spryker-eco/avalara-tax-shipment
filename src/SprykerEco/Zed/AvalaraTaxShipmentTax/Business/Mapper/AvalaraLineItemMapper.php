<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Mapper;

use Generated\Shared\Transfer\AvalaraAddressTransfer;
use Generated\Shared\Transfer\AvalaraLineItemTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use SprykerEco\Zed\AvalaraTaxShipmentTax\Dependency\Facade\AvalaraTaxShipmentTaxToMoneyFacadeInterface;

class AvalaraLineItemMapper implements AvalaraLineItemMapperInterface
{
    public const SHIPMENT_AVALARA_LINE_TYPE = 'shipment';

    /**
     * @uses \Spryker\Shared\Price\PriceConfig::PRICE_MODE_GROSS
     */
    protected const PRICE_MODE_GROSS = 'GROSS_MODE';

    /**
     * @uses \Avalara\TransactionAddressType::C_SHIPTO
     */
    protected const AVALARA_SHIP_TO_ADDRESS_TYPE = 'ShipTo';

    protected const DEFAULT_SHIPMENT_QUANTITY = 1;

    /**
     * @var \SprykerEco\Zed\AvalaraTaxShipmentTax\Dependency\Facade\AvalaraTaxShipmentTaxToMoneyFacadeInterface
     */
    protected $moneyFacade;

    /**
     * @param \SprykerEco\Zed\AvalaraTaxShipmentTax\Dependency\Facade\AvalaraTaxShipmentTaxToMoneyFacadeInterface $moneyFacade
     */
    public function __construct(AvalaraTaxShipmentTaxToMoneyFacadeInterface $moneyFacade)
    {
        $this->moneyFacade = $moneyFacade;
    }

    /**
     * @param \Generated\Shared\Transfer\ShipmentTransfer $shipmentTransfer
     * @param \Generated\Shared\Transfer\AvalaraLineItemTransfer $avalaraLineItemTransfer
     * @param string $priceMode
     *
     * @return \Generated\Shared\Transfer\AvalaraLineItemTransfer
     */
    public function mapShipmentTransferToAvalaraLineItemTransfer(
        ShipmentTransfer $shipmentTransfer,
        AvalaraLineItemTransfer $avalaraLineItemTransfer,
        string $priceMode
    ): AvalaraLineItemTransfer {
        $shipmentMethodTransfer = $shipmentTransfer->getMethodOrFail();
        $avalaraLineItemTransfer
            ->setTaxCode($shipmentMethodTransfer->getAvalaraTaxCodeOrFail())
            ->setQuantity(static::DEFAULT_SHIPMENT_QUANTITY)
            ->setAmount($this->moneyFacade->convertIntegerToDecimal($shipmentMethodTransfer->getStoreCurrencyPriceOrFail()))
            ->setTaxIncluded($this->isTaxIncluded($priceMode))
            ->setItemCode($shipmentMethodTransfer->getShipmentMethodKeyOrFail())
            ->setDescription($shipmentMethodTransfer->getNameOrFail())
            ->setReference1(static::SHIPMENT_AVALARA_LINE_TYPE)
            ->setReference2($shipmentMethodTransfer->getShipmentMethodKeyOrFail());

        if (!$shipmentTransfer->getShippingAddress()) {
            return $avalaraLineItemTransfer;
        }

        $avalaraShippingAddressTransfer = (new AvalaraAddressTransfer())->setType(static::AVALARA_SHIP_TO_ADDRESS_TYPE);
        $avalaraShippingAddressTransfer = $this->mapShipmentTransferToAvalaraAddressTransfer(
            $shipmentTransfer,
            $avalaraShippingAddressTransfer
        );

        return $avalaraLineItemTransfer->setShippingAddress($avalaraShippingAddressTransfer);
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
        $avalaraAddressTransfer->setAddress($shipmentTransfer->getShippingAddressOrFail());

        return $avalaraAddressTransfer;
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
