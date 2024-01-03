<?php
/*
 * Copyright (C) 2018 E-Comprocessing Ltd.
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
 * @copyright   2018 E-Comprocessing Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace Ecomprocessing\Checkout;

use Ecomprocessing\Helpers\TransactionsHelper;
use Genesis\API\Constants\Banks;
use Genesis\API\Constants\Payment\Methods;
use Genesis\API\Constants\Transaction\Names;
use Genesis\API\Constants\Transaction\Parameters\Mobile\ApplePay\PaymentTypes as ApplePaymentTypes;
use Genesis\API\Constants\Transaction\Parameters\Mobile\GooglePay\PaymentTypes as GooglePaymentTypes;
use Genesis\API\Constants\Transaction\Parameters\Threeds\V2\Control\ChallengeIndicators;
use Genesis\API\Constants\Transaction\Parameters\Wallets\PayPal\PaymentTypes as PayPalPaymentTypes;
use Genesis\API\Constants\Transaction\Types;

/**
 * Class Settings
 *
 * @category Ecomprocessing
 *
 * @package Ecomprocessing\Checkout
 * @author  Client Inegrations <client_integrations@e-comprocessing.com>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU, version 2 (GPL-2.0)
 * @link    https://e-comprocessing.com
 */
class Settings extends \Ecomprocessing\Base\Settings
{
    /**
     * Settings Values Prefix
     *
     * @var string
     */
    static protected $prefix = ECOMPROCESSING_CHECKOUT_SETTINGS_PREFIX;

    /**
     * Gets a list of the available transaction types for a payment method
     *
     * @return array
     */
    public static function getTransactionsList()
    {
        $data = array();

        $transactionTypes = Types::getWPFTransactionTypes();
        $excludedTypes    = TransactionsHelper::getRecurringTransactionTypes();

        // Exclude PPRO transaction. This is not standalone transaction type
        array_push($excludedTypes, Types::PPRO);

        // Exclude Google Pay transaction. This will serve Google Pay payment methods
        array_push($excludedTypes, Types::GOOGLE_PAY);

        // Exclude PayPal transaction. This will serve PayPal payment methods
        array_push($excludedTypes, Types::PAY_PAL);

        // Exclude Apple Pay transaction
        array_push($excludedTypes, Types::APPLE_PAY);

        // Exclude Transaction Types
        $transactionTypes = array_diff($transactionTypes, $excludedTypes);

        // Add PPRO types
        $pproTypes = array_map(
            function ($type) {
                return $type . PPRO_TRANSACTION_SUFFIX;
            },
            Methods::getMethods()
        );

        // Add Google Pay types
        $googlePayTypes = array_map(
            function ($type) {
                return GOOGLE_PAY_TRANSACTION_PREFIX . $type;
            },
            [
                GooglePaymentTypes::AUTHORIZE,
                GooglePaymentTypes::SALE
            ]
        );

        // Add PayPal types
        $payPalTypes = array_map(
            function ($type) {
                return PAYPAL_TRANSACTION_PREFIX . $type;
            },
            [
                PayPalPaymentTypes::AUTHORIZE,
                PayPalPaymentTypes::SALE,
                PayPalPaymentTypes::EXPRESS,
            ]
        );

        // Add Apple Pay types
        $applePayTypes = array_map(
            function ($type) {
                return APPLE_PAY_TRANSACTION_PREFIX . $type;
            },
            [
                ApplePaymentTypes::AUTHORIZE,
                ApplePaymentTypes::SALE
            ]
        );

        $transactionTypes = array_merge(
            $transactionTypes,
            $pproTypes,
            $googlePayTypes,
            $payPalTypes,
            $applePayTypes
        );
        asort($transactionTypes);

        foreach ($transactionTypes as $type) {
            $name = Names::getName($type);
            if (!Types::isValidTransactionType($type)) {
                $name = strtoupper($type);
            }

            $data[$type] = $name;
        }

        return $data;
    }

    /**
     * Get available WPF languages
     *
     * @return array
     */
    public static function getAvailableCheckoutLanguages()
    {
        $data     = array();
        $isoCodes = \Genesis\API\Constants\i18n::getAll();

        foreach ($isoCodes as $isoCode) {
            $data[$isoCode] = TransactionsHelper::getLanguageByIsoCode($isoCode);
        }

        return $data;
    }

    /**
     * Get available settings to manage
     * @return array
     */
    public static function getSettingKeys()
    {
        $keys = parent::getSettingKeys();

        static::appendSettingKey($keys, 'ENVIRONMENT', 'TRANSACTION_TYPES');
        static::appendSettingKey(
            $keys,
            'TRANSACTION_TYPES',
            'BANK_CODES'
        );
        $keys[] = static::getPrefix() . 'LANGUAGE';
        $keys[] = static::getPrefix() . 'WPF_TOKENIZATION';
        $keys[] = static::getPrefix() . 'THREEDS_ALLOWED';
        $keys[] = static::getPrefix() . 'THREEDS_CHALLENGE_INDICATOR';
        $keys[] = static::getPrefix() . 'SCA_EXEMPTION';
        $keys[] = static::getPrefix() . 'SCA_EXEMPTION_AMOUNT';

        return $keys;
    }

    /**
     * Get Selected Transaction Types
     */
    public static function getTransactionTypes()
    {
        $transaction_types = static::getSetting("TRANSACTION_TYPES");

        // Trim selected values for payment types and reorder them
        return static::_orderCardTransactionTypes(
            array_map(
                'trim',
                explode(
                    ',',
                    $transaction_types
                )
            )
        );
    }

    /**
     * Get Selected Payment methods for Online banking
     *
     * @return array
     */
    public static function getSelectedBankCodes()
    {
        $bankCodes = static::getSetting("BANK_CODES");

        return
            array_map(
                'trim',
                explode(
                    ',',
                    $bankCodes
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

    /**
     * List of all available Payment method for Online banking
     *
     * @return array
     */
    public static function getAvailableBankCodes()
    {
        return [
            Banks::CPI => 'Interac Combined Pay-in',
            Banks::BCT => 'Bancontact',
            Banks::BLK => 'Blik One Click',
        ];
    }

    /**
     * List of available challenge indicators
     *
     * @return string[]
     */
    public static function getChallengeIndicators()
    {
        return [
            ChallengeIndicators::NO_PREFERENCE          => 'No preference',
            ChallengeIndicators::NO_CHALLENGE_REQUESTED => 'No challenge requested',
            ChallengeIndicators::PREFERENCE             => 'Preference',
            ChallengeIndicators::MANDATE                => 'Mandate'
        ];
    }

    /**
     * Get SCA Exemption value
     *
     * @return string
     */
    public static function getScaExemption()
    {
        return static::getSetting('SCA_EXEMPTION');
    }

    /**
     * Get SCA Exemption amount
     *
     * @return float
     */
    public static function getScaExemptionAmount()
    {
        return max((float)static::getSetting('SCA_EXEMPTION_AMOUNT'), 0);
    }

    /**
     * Order transaction types with Card Transaction types in front
     *
     * @param array $selected_types Selected transaction types
     *
     * @return array
     */
    private static function _orderCardTransactionTypes($selected_types)
    {
        $order = \Genesis\API\Constants\Transaction\Types::getCardTransactionTypes();

        asort($selected_types);

        $sorted_array = array_intersect($order, $selected_types);

        return array_merge(
            $sorted_array,
            array_diff($selected_types, $sorted_array)
        );
    }
}
