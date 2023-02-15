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

namespace Ecomprocessing\Base;

use \Ecomprocessing\Common as EcomprocessingCommon;

abstract class Settings
{
    /**
     * Settings Values Prefix
     * @var string
     */
    static protected $prefix = null;

    /**
     * Inserts a setting key after existing key
     * @param array $keys
     * @param string $existingSettingItem
     * @param string $newSettingItem
     * @param string $position (after or before)
     * @return bool
     */
    protected static function appendSettingKey(
        &$keys,
        $existingSettingItem,
        $newSettingItem,
        $position = 'after'
    ) {
        if (empty($existingSettingItem)) {
            $keys[] = static::getCompleteSettingKey($newSettingItem);
            return true;
        } else {
            $existingSettingItemArrayKey = array_search(
                static::getCompleteSettingKey($existingSettingItem),
                $keys
            );

            if ($existingSettingItemArrayKey > -1) {
                EComprocessingCommon::array_insert(
                    $keys,
                    $existingSettingItemArrayKey + ($position == 'after' ? 1 : 0),
                    static::getCompleteSettingKey($newSettingItem)
                );
                return true;
            }
            return false;
        }
    }

    /**
     * Gets a list of the available transaction types for a payment method
     * @return array
     */
    public static function getTransactionsList()
    {
        return array();
    }

    /**
     * Get available settings to manage
     * @return array
     */
    public static function getSettingKeys()
    {
        return array(
            static::getPrefix() . 'STATUS',
            static::getPrefix() . 'CHECKOUT_PAGE_TITLE',
            static::getPrefix() . 'USERNAME',
            static::getPrefix() . 'PASSWORD',
            static::getPrefix() . 'ENVIRONMENT',
            static::getPrefix() . 'TRANSACTION_TYPE',
            static::getPrefix() . 'ALLOW_PARTIAL_CAPTURE',
            static::getPrefix() . 'ALLOW_PARTIAL_REFUND',
            static::getPrefix() . 'ALLOW_VOID_TRANSACTIONS',
            static::getPrefix() . 'SORT_ORDER',
            static::getPrefix() . 'ORDER_STATUS_ID',
            static::getPrefix() . 'FAILED_ORDER_STATUS_ID',
            static::getPrefix() . 'PROCESSED_ORDER_STATUS_ID',
            static::getPrefix() . 'REFUNDED_ORDER_STATUS_ID',
            static::getPrefix() . 'CANCELED_ORDER_STATUS_ID'
        );
    }

    /**
     * Checks if module is installed
     * @return bool
     * @throws \Exception
     */
    public static function getIsInstalled()
    {
        if (empty(static::getPrefix())) {
            throw new \Exception("SettingsPrefix not set");
        }

        return defined(static::getPrefix() . "STATUS");
    }

    /**
     * Get Setting keys prefix
     * @return string
     */
    public static function getPrefix()
    {
        return static::$prefix;
    }

    public static function getIsAvailableOnCheckoutPage()
    {
        return
            static::getIsConfigured() &&
            static::getStatus();
    }

    /**
     * Get if module required settings are set properly
     * @return bool
     */
    public static function getIsConfigured()
    {
        return
            !empty(static::getUserName()) &&
            !empty(static::getPassword());
    }

    /**
     * Get the whole setting key including the module prefix
     * @param string $var
     * @return string
     */
    public static function getCompleteSettingKey($var)
    {
        return static::getPrefix() . $var;
    }

    /**
     * Get module string setting value
     * @param string $var
     * @return string
     */
    protected static function getSetting($var)
    {
        $var = static::getCompleteSettingKey($var);
        return defined($var) ? constant($var) : '';
    }

    /**
     * Get module bool setting value
     * @param string $var
     * @return bool
     */
    private static function getBoolSetting($var)
    {
        return (static::getSetting($var) == 'true' ? true : false);
    }

    /**
     * Get if module is enabled
     * @return bool
     */
    public static function getStatus()
    {
        return static::getBoolSetting("STATUS");
    }

    /**
     * Get Module title, which will be displayed on the Checkout Page
     * @param string $default
     * @return string
     */
    public static function getCheckoutPageTitle($default = '')
    {
        return (static::getSetting("CHECKOUT_PAGE_TITLE") ?: $default);
    }

    /**
     * Get Genesis Login Setting Value
     * @return string
     */
    public static function getUserName()
    {
        return static::getSetting("USERNAME");
    }

    /**
     * Get Genesis Password Setting Value
     * @return string
     */
    public static function getPassword()
    {
        return static::getSetting("PASSWORD");
    }

    /**
     * Get Module Test Mode (Staging or Production)
     * @return bool
     */
    public static function getIsLiveMode()
    {
        return static::getBoolSetting('ENVIRONMENT');
    }

    /**
     * Get Partial Capture Allowed setting value
     * @return bool
     */
    public static function getIsPartialCaptureAllowed()
    {
        return static::getBoolSetting("ALLOW_PARTIAL_CAPTURE");
    }

    /**
     * Get Partial Refund Allowed setting value
     * @return bool
     */
    public static function getIsPartialRefundAllowed()
    {
        return static::getBoolSetting("ALLOW_PARTIAL_REFUND");
    }

    /**
     * Get Void Transaction Allowed setting value
     * @return bool
     */
    public static function getIsVoidTransactionAllowed()
    {
        return static::getBoolSetting("ALLOW_VOID_TRANSACTIONS");
    }

    /**
     * Sort Order of this payment option on the customer payment page
     * @return int
     */
    public static function getSortOrder()
    {
        return (int) static::getSetting("SORT_ORDER");
    }

    /**
     * Get Default Order Status ID Setting Value
     * @return int
     */
    public static function getOrderStatusID()
    {
        return (int) static::getSetting("ORDER_STATUS_ID");
    }

    /**
     * Get Failed Order Status ID Setting Value
     * @return int
     */
    public static function getFailedOrderStatusID()
    {
        return (int) static::getSetting("FAILED_ORDER_STATUS_ID");
    }

    /**
     * Get Processed Order Status ID Setting Value
     * @return int
     */
    public static function getProcessedOrderStatusID()
    {
        return (int) static::getSetting("PROCESSED_ORDER_STATUS_ID");
    }

    /**
     * Get Refunded Order Status ID Setting Value
     * @return int
     */
    public static function getRefundedOrderStatusID()
    {
        return (int) static::getSetting("REFUNDED_ORDER_STATUS_ID");
    }

    /**
     * Get Canceled Order Status ID Setting Value
     * @return int
     */
    public static function getCanceledOrderStatusID()
    {
        return (int) static::getSetting("CANCELED_ORDER_STATUS_ID");
    }

    /**
     * Get WPF Tokenization setting value
     *
     * @return bool
     */
    public static function isWpfTokenizationEnabled()
    {
        return static::getBoolSetting('WPF_TOKENIZATION');
    }
}
