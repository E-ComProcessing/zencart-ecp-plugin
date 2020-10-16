<?php
/*
 * Copyright (C) 2016 E-Comprocessing™
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author      E-Comprocessing
 * @copyright   2016 E-Comprocessing Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace EComProcessing\Checkout;

class Settings extends \EComProcessing\Base\Settings
{
    /**
     * Settings Values Prefix
     * @var string
     */
    static protected $prefix = ECOMPROCESSING_CHECKOUT_SETTINGS_PREFIX;

    /**
     * Gets a list of the available transaction types for a payment method
     * @return array
     */
    public static function getTransactionsList()
    {
        return array(
            \Genesis\API\Constants\Transaction\Types::ABNIDEAL            => "ABN iDEAL",
            \Genesis\API\Constants\Transaction\Types::AUTHORIZE           => "Authorize",
            \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D        => "Authorize 3D",
            \Genesis\API\Constants\Transaction\Types::CASHU               => "CashU",
            \Genesis\API\Constants\Payment\Methods::EPS                   => "eps",
            \Genesis\API\Constants\Payment\Methods::GIRO_PAY              => "GiroPay",
            \Genesis\API\Constants\Transaction\Types::NETELLER            => "Neteller",
            \Genesis\API\Constants\Payment\Methods::QIWI                  => "Qiwi",
            \Genesis\API\Constants\Transaction\Types::PAYBYVOUCHER_SALE   => "PayByVoucher (Sale)",
            \Genesis\API\Constants\Transaction\Types::PAYBYVOUCHER_YEEPAY => "PayByVoucher (oBeP)",
            \Genesis\API\Constants\Transaction\Types::PAYSAFECARD         => "PaySafeCard",
            \Genesis\API\Constants\Payment\Methods::PRZELEWY24            => "Przelewy24",
            \Genesis\API\Constants\Transaction\Types::POLI                => "POLi",
            \Genesis\API\Constants\Payment\Methods::SAFETY_PAY            => "SafetyPay",
            \Genesis\API\Constants\Transaction\Types::SALE                => "Sale",
            \Genesis\API\Constants\Transaction\Types::SALE_3D             => "Sale 3D",
            \Genesis\API\Constants\Transaction\Types::SOFORT              => "SOFORT",
            \Genesis\API\Constants\Payment\Methods::TELEINGRESO           => "teleingreso",
            \Genesis\API\Constants\Payment\Methods::TRUST_PAY             => "TrustPay",
            \Genesis\API\Constants\Transaction\Types::WEBMONEY            => "WebMoney"
        );
    }

    public static function getAvailableCheckoutLanguages()
    {
        return array(
            'en' => 'English',
            'it' => 'Italian',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'ja' => 'Japanese',
            'zh' => 'Chinese',
            'ar' => 'Arabic',
            'pt' => 'Portuguese',
            'tr' => 'Turkish',
            'ru' => 'Russian',
            'hi' => 'Hindi',
            'bg' => 'Bulgarian'
        );
    }

    /**
     * Get available settings to manage
     * @return array
     */
    public static function getSettingKeys()
    {
        $keys = parent::getSettingKeys();

        static::appendSettingKey($keys, 'ENVIRONMENT', 'TRANSACTION_TYPES');
        $keys[] = static::getPrefix() . "LANGUAGE";

        return $keys;
    }

    /**
     * Get Selected Transaction Types
     */
    public static function getTransactionTypes()
    {
        $transaction_types = static::getSetting("TRANSACTION_TYPES");
        return
            array_map(
                'trim',
                explode(
                    ',',
                    $transaction_types
                )
            );
    }

    /**
     * Get Checkout Language for the Genesis WPF
     * @param string $default
     * @return string
     */
    public static function getLanguage($default = 'en')
    {
        return (static::getSetting("LANGUAGE") ?: $default);
    }
}
