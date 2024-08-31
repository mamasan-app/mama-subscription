<?php

namespace App\Support;

use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use NumberFormatter;

class MoneyFormatter
{
    public function __construct(protected Money $amount)
    {
    }

    public static function make(Money $amount): self
    {
        return new self($amount);
    }

    public function format(): string
    {
        $currencies = new ISOCurrencies();

        $numberFormatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);

        return $moneyFormatter->format($this->amount);
    }
}
