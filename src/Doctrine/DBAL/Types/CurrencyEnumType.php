<?php
namespace App\Doctrine\DBAL\Types;

class CurrencyEnumType extends AbstractEnumType
{
    const CURRENCY_ENUM = 'currency_enum'; 

    protected $values = ['USD', 'EUR', 'GBP', 'BTC'];

    public static function create()
    {
        return new self();
    }
}
