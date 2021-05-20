<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipment\Communication\Plugin\AvalaraTax;

use Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerEco\Zed\AvalaraTaxExtension\Dependency\Plugin\CreateTransactionRequestAfterPluginInterface;

/**
 * @method \SprykerEco\Zed\AvalaraTaxShipment\Business\AvalaraTaxShipmentFacadeInterface getFacade()
 * @method \SprykerEco\Zed\AvalaraTaxShipment\AvalaraTaxShipmentConfig getConfig()
 */
class AvalaraShipmentCreateTransactionRequestAfterPlugin extends AbstractPlugin implements CreateTransactionRequestAfterPluginInterface
{
    /**
     * {@inheritDoc}
     * - Calculates taxes for shipment methods based on `AvalaraCreateTransactionResponseTransfer`.
     * - Sets tax rate to `CalculableObjectTransfer.items.shipment.method.taxRate`.
     * - Sets tax rate and tax amount to `CalculableObjectTransfer.originalQuote.expenses` according to shipment method.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     * @param \Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer $avalaraCreateTransactionResponseTransfer
     *
     * @return \Generated\Shared\Transfer\CalculableObjectTransfer
     */
    public function execute(
        CalculableObjectTransfer $calculableObjectTransfer,
        AvalaraCreateTransactionResponseTransfer $avalaraCreateTransactionResponseTransfer
    ): CalculableObjectTransfer {
        return $this->getFacade()
            ->calculateShipmentTax(
                $calculableObjectTransfer,
                $avalaraCreateTransactionResponseTransfer
            );
    }
}
