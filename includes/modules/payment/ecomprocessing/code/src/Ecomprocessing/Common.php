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

namespace Ecomprocessing;

class Common
{
    /**
     * Get formatted datetime object
     * @param mixed $timestamp
     * @return string
     */
    public static function formatTimeStamp($timestamp)
    {
        return ($timestamp instanceof \DateTime)
            ? $timestamp->format('Y-m-d H:i:s')
            : $timestamp;
    }

    /**
     * Recursive function used in the process of sorting
     * the Transactions list
     *
     * @param $array_out array
     * @param $val array
     * @param $array_asc array
     */
    public static function sortTransactionByRelation(&$array_out, $val, $array_asc)
    {
        if (isset($val['org_key'])) {
            $array_out[$val['org_key']] = $val;

            if (isset($val['children']) && sizeof($val['children'])) {
                foreach ($val['children'] as $id) {
                    static::sortTransactionByRelation($array_out, $array_asc[$id], $array_asc);
                }
            }

            unset($array_out[$val['org_key']]['children'], $array_out[$val['org_key']]['org_key']);
        }
    }

    /**
     * Get Server Remote Address (Used for sending Requests to Genesis)
     * @return string
     */
    public static function getServerRemoteAddress()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Get a formatted transaction value for the Admin Transactions Panel
     * @param float $amount
     * @param array $currency
     * @return string
     */
    public static function formatTransactionValue($amount, $currency)
    {
        return number_format(
            $amount,
            $currency['decimalPlaces'],
            $currency['decimalSeparator'],
            $currency["thousandSeparator"]
        );
    }

    /**
     * Build Currency array from the order currency code
     * @param string $currencyCode
     * @return array|bool
     */
    public static function getZenCurrency($currencyCode)
    {
        global $db;

        $sql = 'select * from `' . TABLE_CURRENCIES . '` WHERE `code` = :code';

        $sql = $db->bindVars($sql, ':code', $currencyCode, 'string');
        $query = $db->Execute($sql);

        if ($query->RecordCount() == 1) {
            $currencySymbol = ($query->fields['symbol_left'] ?: $query->fields['symbol_right']);
            return array(
                'sign' => $currencySymbol,
                'iso_code' => $currencyCode,
                'decimalPlaces' => $query->fields['decimal_places'],
                'decimalSeparator' => $query->fields['decimal_point'],
                'thousandSeparator' => "" /* Genesis does not allow thousand separator */
            );
        }

        return false;
    }


    /**
     * @param array      $array
     * @param int|string $position
     * @param mixed      $insert
     */
    public static function array_insert(&$array, $position, $insert)
    {
        if (is_int($position)) {
            array_splice($array, $position, 0, $insert);
        } else {
            $pos   = array_search($position, array_keys($array));
            $array = array_merge(
                array_slice($array, 0, $pos),
                $insert,
                array_slice($array, $pos)
            );
        }
    }

    /**
     * Get If SSL Enabled for the Front Site
     * @return bool
     */
    public static function getIsSSLEnabled()
    {
        return
            (
                (defined('ENABLE_SSL') && strtolower(ENABLE_SSL) == 'true') ||
                (defined('ENABLE_SSL_CATALOG') && strtolower(ENABLE_SSL_CATALOG) == 'true')
            ) &&
            (substr(HTTP_SERVER, 0, 5) == 'https') ? true : false;
    }

    /**
     * Get Credit Card formatted year
     * @param string $expiry_year
     * @return string
     */
    public static function getCreditCardExpirationYear($expiry_year)
    {
        if (strlen($expiry_year) == 2) {
            $expiry_year = substr(date('Y'), 0, 2) . $expiry_year;
        }

        return $expiry_year;
    }

    /**
     * Builds an array for the Installer
     *
     * @param array $options Input array to be converted
     * @return string
     */
    public static function buildSettingsDropDownOptions($options)
    {
        $result = "array(";

        foreach ($options as $option_key => $option_display_name) {
            $result .= "array(
                ''id'' => ''{$option_key}'',
                ''text'' => ''{$option_display_name}''
            ),";
        }
        $result .= ")";

        return $result;
    }
}
