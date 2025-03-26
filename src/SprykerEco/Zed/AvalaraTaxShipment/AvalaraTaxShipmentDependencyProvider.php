<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipment;

use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;
use SprykerEco\Zed\AvalaraTaxShipment\Dependency\Facade\AvalaraTaxShipmentToMoneyFacadeBridge;
use SprykerEco\Zed\AvalaraTaxShipment\Dependency\Service\AvalaraTaxShipmentToShipmentServiceBridge;
use SprykerEco\Zed\AvalaraTaxShipment\Dependency\Service\AvalaraTaxShipmentToUtilEncodingServiceBridge;

/**
 * @method \SprykerEco\Zed\AvalaraTaxShipment\AvalaraTaxShipmentConfig getConfig()
 */
class AvalaraTaxShipmentDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * @var string
     */
    public const FACADE_MONEY = 'FACADE_MONEY';

    /**
     * @var string
     */
    public const SERVICE_SHIPMENT = 'SERVICE_SHIPMENT';

    /**
     * @var string
     */
    public const SERVICE_UTIL_ENCODING = 'SERVICE_UTIL_ENCODING';

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->addMoneyFacade($container);
        $container = $this->addShipmentService($container);
        $container = $this->addUtilEncodingService($container);

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addMoneyFacade(Container $container): Container
    {
        $container->set(static::FACADE_MONEY, function (Container $container) {
            return new AvalaraTaxShipmentToMoneyFacadeBridge($container->getLocator()->money()->facade());
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addShipmentService(Container $container): Container
    {
        $container->set(static::SERVICE_SHIPMENT, function (Container $container) {
            return new AvalaraTaxShipmentToShipmentServiceBridge($container->getLocator()->shipment()->service());
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addUtilEncodingService(Container $container): Container
    {
        $container->set(static::SERVICE_UTIL_ENCODING, function (Container $container) {
            return new AvalaraTaxShipmentToUtilEncodingServiceBridge($container->getLocator()->utilEncoding()->service());
        });

        return $container;
    }
}
