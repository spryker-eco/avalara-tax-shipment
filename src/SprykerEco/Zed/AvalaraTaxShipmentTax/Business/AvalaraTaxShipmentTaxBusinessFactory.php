<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipmentTax\Business;

use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use SprykerEco\Zed\AvalaraTaxShipmentTax\AvalaraTaxShipmentTaxDependencyProvider;
use SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Calculator\MultiShipmentAbstractShipmentAvalaraTaxCalculator;
use SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Calculator\ShipmentAvalaraTaxCalculatorInterface;
use SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Calculator\SingleAddressShipmentAvalaraTaxCalculator;
use SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Expander\AvalaraCreateTransactionRequestExpander;
use SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Expander\AvalaraCreateTransactionRequestExpanderInterface;
use SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Mapper\AvalaraLineItemMapper;
use SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Mapper\AvalaraLineItemMapperInterface;
use SprykerEco\Zed\AvalaraTaxShipmentTax\Business\StrategyResolver\ShipmentTaxCalculatorStrategyResolver;
use SprykerEco\Zed\AvalaraTaxShipmentTax\Business\StrategyResolver\ShipmentTaxCalculatorStrategyResolverInterface;
use SprykerEco\Zed\AvalaraTaxShipmentTax\Dependency\Facade\AvalaraTaxShipmentTaxToMoneyFacadeInterface;
use SprykerEco\Zed\AvalaraTaxShipmentTax\Dependency\Service\AvalaraTaxShipmentTaxToShipmentServiceInterface;
use SprykerEco\Zed\AvalaraTaxShipmentTax\Dependency\Service\AvalaraTaxShipmentTaxToUtilEncodingServiceInterface;

/**
 * @method \SprykerEco\Zed\AvalaraTaxShipmentTax\AvalaraTaxShipmentTaxConfig getConfig()
 */
class AvalaraTaxShipmentTaxBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Calculator\ShipmentAvalaraTaxCalculatorInterface
     */
    public function createSingleAddressShipmentAvalaraTaxCalculator(): ShipmentAvalaraTaxCalculatorInterface
    {
        return new SingleAddressShipmentAvalaraTaxCalculator(
            $this->getMoneyFacade(),
            $this->getShipmentService(),
            $this->getUtilEncodingService()
        );
    }

    /**
     * @return \SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Calculator\ShipmentAvalaraTaxCalculatorInterface
     */
    public function createMultiAddressShipmentAvalaraTaxCalculator(): ShipmentAvalaraTaxCalculatorInterface
    {
        return new MultiShipmentAbstractShipmentAvalaraTaxCalculator(
            $this->getMoneyFacade(),
            $this->getShipmentService(),
            $this->getUtilEncodingService()
        );
    }

    /**
     * @return \SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Expander\AvalaraCreateTransactionRequestExpanderInterface
     */
    public function createAvalaraCreateTransactionRequestExpander(): AvalaraCreateTransactionRequestExpanderInterface
    {
        return new AvalaraCreateTransactionRequestExpander($this->createAvalaraLineItemMapper());
    }

    /**
     * @return \SprykerEco\Zed\AvalaraTaxShipmentTax\Business\Mapper\AvalaraLineItemMapperInterface
     */
    public function createAvalaraLineItemMapper(): AvalaraLineItemMapperInterface
    {
        return new AvalaraLineItemMapper($this->getMoneyFacade());
    }

    /**
     * @return \SprykerEco\Zed\AvalaraTaxShipmentTax\Dependency\Facade\AvalaraTaxShipmentTaxToMoneyFacadeInterface
     */
    public function getMoneyFacade(): AvalaraTaxShipmentTaxToMoneyFacadeInterface
    {
        return $this->getProvidedDependency(AvalaraTaxShipmentTaxDependencyProvider::FACADE_MONEY);
    }

    /**
     * @return \SprykerEco\Zed\AvalaraTaxShipmentTax\Dependency\Service\AvalaraTaxShipmentTaxToShipmentServiceInterface
     */
    public function getShipmentService(): AvalaraTaxShipmentTaxToShipmentServiceInterface
    {
        return $this->getProvidedDependency(AvalaraTaxShipmentTaxDependencyProvider::SERVICE_SHIPMENT);
    }

    /**
     * @return \SprykerEco\Zed\AvalaraTaxShipmentTax\Dependency\Service\AvalaraTaxShipmentTaxToUtilEncodingServiceInterface
     */
    public function getUtilEncodingService(): AvalaraTaxShipmentTaxToUtilEncodingServiceInterface
    {
        return $this->getProvidedDependency(AvalaraTaxShipmentTaxDependencyProvider::SERVICE_UTIL_ENCODING);
    }

    /**
     * @deprecated Exists for Backward Compatibility reasons only. Use {@link createMultiAddressShipmentAvalaraTaxCalculator()} instead.
     *
     * @return \SprykerEco\Zed\AvalaraTaxShipmentTax\Business\StrategyResolver\ShipmentTaxCalculatorStrategyResolverInterface
     */
    public function createShipmentTaxCalculatorStrategyResolver(): ShipmentTaxCalculatorStrategyResolverInterface
    {
        $strategyContainer = [];

        $strategyContainer[ShipmentTaxCalculatorStrategyResolver::STRATEGY_KEY_WITHOUT_MULTI_SHIPMENT] = function () {
            return $this->createSingleAddressShipmentAvalaraTaxCalculator();
        };

        $strategyContainer[ShipmentTaxCalculatorStrategyResolver::STRATEGY_KEY_WITH_MULTI_SHIPMENT] = function () {
            return $this->createMultiAddressShipmentAvalaraTaxCalculator();
        };

        return new ShipmentTaxCalculatorStrategyResolver($strategyContainer);
    }
}
