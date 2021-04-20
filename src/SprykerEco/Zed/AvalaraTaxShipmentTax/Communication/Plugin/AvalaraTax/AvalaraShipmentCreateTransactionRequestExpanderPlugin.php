<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipmentTax\Communication\Plugin\AvalaraTax;

use Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerEco\Zed\AvalaraTaxExtension\Dependency\Plugin\CreateTransactionRequestExpanderPluginInterface;

/**
 * @method \SprykerEco\Zed\AvalaraTaxShipmentTax\Business\AvalaraTaxShipmentTaxFacadeInterface getFacade()
 * @method \SprykerEco\Zed\AvalaraTaxShipmentTax\AvalaraTaxShipmentTaxConfig getConfig()
 */
class AvalaraShipmentCreateTransactionRequestExpanderPlugin extends AbstractPlugin implements CreateTransactionRequestExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Expands `AvalaraCreateTransactionRequestTransfer` with shipments.
     *
     * @api
     * @api
     *
     * @param \Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return \Generated\Shared\Transfer\AvalaraCreateTransactionRequestTransfer
     */
    public function expand(
        AvalaraCreateTransactionRequestTransfer $avalaraCreateTransactionRequestTransfer,
        CalculableObjectTransfer $calculableObjectTransfer
    ): AvalaraCreateTransactionRequestTransfer {
        return $this->getFacade()->expandAvalaraCreateTransactionRequest(
            $avalaraCreateTransactionRequestTransfer,
            $calculableObjectTransfer
        );
    }
}
