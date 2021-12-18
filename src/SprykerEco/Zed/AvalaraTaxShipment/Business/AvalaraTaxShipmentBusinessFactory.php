<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipment\Business;

use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use SprykerEco\Zed\AvalaraTaxShipment\AvalaraTaxShipmentDependencyProvider;
use SprykerEco\Zed\AvalaraTaxShipment\Business\Calculator\MultiShipmentAbstractShipmentAvalaraTaxCalculator;
use SprykerEco\Zed\AvalaraTaxShipment\Business\Calculator\ShipmentAvalaraTaxCalculatorInterface;
use SprykerEco\Zed\AvalaraTaxShipment\Business\Calculator\SingleAddressShipmentAvalaraTaxCalculator;
use SprykerEco\Zed\AvalaraTaxShipment\Business\Expander\AvalaraCreateTransactionRequestExpander;
use SprykerEco\Zed\AvalaraTaxShipment\Business\Expander\AvalaraCreateTransactionRequestExpanderInterface;
use SprykerEco\Zed\AvalaraTaxShipment\Business\Mapper\AvalaraLineItemMapper;
use SprykerEco\Zed\AvalaraTaxShipment\Business\Mapper\AvalaraLineItemMapperInterface;
use SprykerEco\Zed\AvalaraTaxShipment\Business\StrategyResolver\ShipmentTaxCalculatorStrategyResolver;
use SprykerEco\Zed\AvalaraTaxShipment\Business\StrategyResolver\ShipmentTaxCalculatorStrategyResolverInterface;
use SprykerEco\Zed\AvalaraTaxShipment\Dependency\Facade\AvalaraTaxShipmentToMoneyFacadeInterface;
use SprykerEco\Zed\AvalaraTaxShipment\Dependency\Service\AvalaraTaxShipmentToShipmentServiceInterface;
use SprykerEco\Zed\AvalaraTaxShipment\Dependency\Service\AvalaraTaxShipmentToUtilEncodingServiceInterface;

/**
 * @method \SprykerEco\Zed\AvalaraTaxShipment\AvalaraTaxShipmentConfig getConfig()
 */
class AvalaraTaxShipmentBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \SprykerEco\Zed\AvalaraTaxShipment\Business\Calculator\ShipmentAvalaraTaxCalculatorInterface
     */
    public function createSingleAddressShipmentAvalaraTaxCalculator(): ShipmentAvalaraTaxCalculatorInterface
    {
        return new SingleAddressShipmentAvalaraTaxCalculator(
            $this->getMoneyFacade(),
            $this->getShipmentService(),
            $this->getUtilEncodingService(),
        );
    }

    /**
     * @return \SprykerEco\Zed\AvalaraTaxShipment\Business\Calculator\ShipmentAvalaraTaxCalculatorInterface
     */
    public function createMultiAddressShipmentAvalaraTaxCalculator(): ShipmentAvalaraTaxCalculatorInterface
    {
        return new MultiShipmentAbstractShipmentAvalaraTaxCalculator(
            $this->getMoneyFacade(),
            $this->getShipmentService(),
            $this->getUtilEncodingService(),
        );
    }

    /**
     * @return \SprykerEco\Zed\AvalaraTaxShipment\Business\Expander\AvalaraCreateTransactionRequestExpanderInterface
     */
    public function createAvalaraCreateTransactionRequestExpander(): AvalaraCreateTransactionRequestExpanderInterface
    {
        return new AvalaraCreateTransactionRequestExpander(
            $this->createAvalaraLineItemMapper(),
            $this->getShipmentService(),
        );
    }

    /**
     * @return \SprykerEco\Zed\AvalaraTaxShipment\Business\Mapper\AvalaraLineItemMapperInterface
     */
    public function createAvalaraLineItemMapper(): AvalaraLineItemMapperInterface
    {
        return new AvalaraLineItemMapper($this->getMoneyFacade());
    }

    /**
     * @return \SprykerEco\Zed\AvalaraTaxShipment\Dependency\Facade\AvalaraTaxShipmentToMoneyFacadeInterface
     */
    public function getMoneyFacade(): AvalaraTaxShipmentToMoneyFacadeInterface
    {
        return $this->getProvidedDependency(AvalaraTaxShipmentDependencyProvider::FACADE_MONEY);
    }

    /**
     * @return \SprykerEco\Zed\AvalaraTaxShipment\Dependency\Service\AvalaraTaxShipmentToShipmentServiceInterface
     */
    public function getShipmentService(): AvalaraTaxShipmentToShipmentServiceInterface
    {
        return $this->getProvidedDependency(AvalaraTaxShipmentDependencyProvider::SERVICE_SHIPMENT);
    }

    /**
     * @return \SprykerEco\Zed\AvalaraTaxShipment\Dependency\Service\AvalaraTaxShipmentToUtilEncodingServiceInterface
     */
    public function getUtilEncodingService(): AvalaraTaxShipmentToUtilEncodingServiceInterface
    {
        return $this->getProvidedDependency(AvalaraTaxShipmentDependencyProvider::SERVICE_UTIL_ENCODING);
    }

    /**
     * @deprecated Exists for Backward Compatibility reasons only. Use {@link createMultiAddressShipmentAvalaraTaxCalculator()} instead.
     *
     * @return \SprykerEco\Zed\AvalaraTaxShipment\Business\StrategyResolver\ShipmentTaxCalculatorStrategyResolverInterface
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
