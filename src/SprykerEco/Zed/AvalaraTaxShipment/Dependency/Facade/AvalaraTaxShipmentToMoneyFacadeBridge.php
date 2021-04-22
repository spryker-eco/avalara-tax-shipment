<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxShipment\Dependency\Facade;

class AvalaraTaxShipmentToMoneyFacadeBridge implements AvalaraTaxShipmentToMoneyFacadeInterface
{
    /**
     * @var \Spryker\Zed\Money\Business\MoneyFacadeInterface
     */
    protected $moneyFacade;

    /**
     * @param \Spryker\Zed\Money\Business\MoneyFacadeInterface $moneyFacade
     */
    public function __construct($moneyFacade)
    {
        $this->moneyFacade = $moneyFacade;
    }

    /**
     * @param int $value
     *
     * @return float
     */
    public function convertIntegerToDecimal($value)
    {
        return $this->moneyFacade->convertIntegerToDecimal($value);
    }

    /**
     * @param float $value
     *
     * @return int
     */
    public function convertDecimalToInteger($value)
    {
        return $this->moneyFacade->convertDecimalToInteger($value);
    }
}
