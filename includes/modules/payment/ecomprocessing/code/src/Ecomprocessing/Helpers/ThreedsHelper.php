<?php

/**
 * Copyright (C) 2018-2023 E-Comprocessing Ltd.
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
 * PHP version 7
 *
 * @category  Ecomprocessing
 * @package   Ecomprocessing\Helpers
 * @author    Client Inegrations <client_integrations@e-comprocessing.com>
 * @copyright 2018-2023 E-Comprocessing Ltd.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU, version 2 (GPL-2.0)
 * @link      https://e-comprocessing.com
 */

namespace Ecomprocessing\Helpers;

use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\PasswordChangeIndicators;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\RegistrationIndicators;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\ShippingAddressUsageIndicators;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\UpdateIndicators;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\MerchantRisk\ReorderItemIndicators;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\Purchase\Categories;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\MerchantRisk\DeliveryTimeframes;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\MerchantRisk\ShippingIndicators;
use DateInterval;
use DateTime;

/**
 * Class ThreedsHelper
 *
 * @category Ecomprocessing
 *
 * @package Ecomprocessing\Helpers
 * @author  Client Inegrations <client_integrations@e-comprocessing.com>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU, version 2 (GPL-2.0)
 * @link    https://e-comprocessing.com
 */
class ThreedsHelper
{
    /**
     * ZenCert datetime format
     */
    const ZENCART_DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * Indicator value constants
     */
    const CURRENT_TRANSACTION_INDICATOR       = 'current_transaction';
    const LESS_THAN_30_DAYS_INDICATOR         = 'less_than_30_days';
    const MORE_THAN_30_LESS_THAN_60_INDICATOR = 'more_30_less_60_days';
    const MORE_THAN_60_DAYS_INDICATOR         = 'more_than_60_days';

    /**
     * Activity periods
     */
    const ACTIVITY_24_HOURS = 'PT24H';
    const ACTIVITY_6_MONTHS = 'P6M';
    const ACTIVITY_1_YEAR   = 'P1Y';

    /**
     * Ids of statuses when order is completed successfully
     *
     * @var array $completeStatuses
     */
    private static $completeStatuses = [3];

    /**
     * Get type of the purchase
     *
     * @param bool $isVirtualCart Are only downloadable products (services) in cart
     *
     * @return string
     */
    public static function getThreedsPurchaseCategory($isVirtualCart)
    {
        return $isVirtualCart ?
            Categories::SERVICE :
            Categories::GOODS;
    }

    /**
     * Get delivery timeframe
     *
     * @param bool $isVirtualCart Are only downloadable products (services) in cart
     *
     * @return string
     */
    public static function getThreedsDeliveryTimeframe($isVirtualCart)
    {
        return $isVirtualCart ?
            DeliveryTimeframes::ELECTRONICS :
            DeliveryTimeframes::ANOTHER_DAY;
    }

    /**
     * Get customer's creation and last modified dates
     *
     * @param int    $customerId Customer ID
     * @param object $dbObj      Database object
     *
     * @return array|false
     */
    public static function getCustomerInfo($customerId, $dbObj)
    {
        $infoQueryRaw = sprintf(
            "SELECT customers_info_date_account_created AS date_account_created,
            COALESCE (
		        customers_info_date_account_last_modified,
		        customers_info_date_account_created
	        ) AS date_account_last_modified
            FROM %s
            WHERE customers_info_id = :customer_id
            ",
            TABLE_CUSTOMERS_INFO
        );
        $infoQuery = $dbObj->Execute(
            $dbObj->bindVars(
                $infoQueryRaw,
                ':customer_id',
                $customerId,
                'integer'
            ),
            false, // zf_limit
            false, // zf_cache
            0,     // zf_cachetime
            true   // remove_from_queryCache
        );

        return $infoQuery->fields;
    }

    /**
     * Get list of all customer's orders
     *
     * @param int    $customerId Customer ID
     * @param object $dbObj      Database object
     *
     * @return array
     */
    public static function getCustomerOrders($customerId, $dbObj)
    {
        $customerOrdersQueryRaw = sprintf(
            "SELECT * FROM %s
            WHERE customers_id = :customers_id AND payment_module_code = '%s'
            ORDER BY date_purchased ASC
        ",
            TABLE_ORDERS,
            ECOMPROCESSING_CHECKOUT_CODE
        );

        $customerOrders = $dbObj->Execute(
            $dbObj->bindVars(
                $customerOrdersQueryRaw,
                ':customers_id',
                $customerId,
                'integer'
            ),
            false, // zf_limit
            false, // zf_cache
            0,     // zf_cachetime
            true   // remove_from_queryCache
        );

        $orders = array();
        foreach ($customerOrders as $customerOrder) {
            $orders[] = $customerOrder;
        }

        return $orders;
    }

    /**
     * Find number of customer's orders for a period
     *
     * @param array $customerOrders Customer's all orders
     *
     * @return array
     */
    public static function findNumberOfOrdersForPeriod($customerOrders)
    {
        $numberOfOrdersLast24h  = 0;
        $numberOfOrdersLast6M   = 0;
        $numberOfOrdersLastYear = 0;

        if (is_array($customerOrders) && count($customerOrders) > 0) {
            $customerOrders    = array_reverse($customerOrders);
            $startDateLast24h  = (new DateTime())->sub(
                new DateInterval(self::ACTIVITY_24_HOURS)
            );
            $startDateLast6m   = (new DateTime())->sub(
                new DateInterval(self::ACTIVITY_6_MONTHS)
            );
            $previousYear      = (new DateTime())->sub(
                new DateInterval(self::ACTIVITY_1_YEAR)
            )
                ->format('Y');
            $startDateLastYear = (new DateTime())
                ->setDate($previousYear, 1, 1)
                ->setTime(0, 0, 0);
            $endDateLastYear   = (new DateTime())
                ->setDate($previousYear, 12, 31)
                ->setTime(23, 59, 59);

            foreach ($customerOrders as $customerOrder) {
                $orderDate = DateTime::createFromFormat(
                    self::ZENCART_DATETIME_FORMAT,
                    $customerOrder['date_purchased']
                );

                // We don't need orders older than a year
                if ($orderDate < $startDateLastYear) {
                    break;
                }

                // Get order details only if the order
                // was placed within the last 6 months
                if ($orderDate >= $startDateLast6m) {
                    // Check if the order status is complete or shipped
                    $numberOfOrdersLast6M += (
                        in_array(
                            $customerOrder['orders_status'],
                            self::$completeStatuses
                        )
                    )
                    ? 1 : 0;
                }

                $numberOfOrdersLast24h  += ($orderDate >= $startDateLast24h) ? 1 : 0;
                $numberOfOrdersLastYear += ($orderDate <= $endDateLastYear) ? 1 : 0;
            }
        }

        return [
            'last_24h'  => $numberOfOrdersLast24h,
            'last_6m'   => $numberOfOrdersLast6M,
            'last_year' => $numberOfOrdersLastYear
        ];
    }

    /**
     * Get shipping indicator
     *
     * @param object $data          Order data object
     * @param bool   $isVirtualCart Are only downloadable products (services) in cart
     *
     * @return string
     */
    public static function getShippingIndicator($data, $isVirtualCart)
    {
        if ($isVirtualCart) {
            return ShippingIndicators::DIGITAL_GOODS;
        }

        $indicator = ShippingIndicators::STORED_ADDRESS;

        if (self::areAddressesSame($data->order->billing, $data->order->delivery)) {
            $indicator = ShippingIndicators::SAME_AS_BILLING;
        }

        return $indicator;
    }

    /**
     * Get reorder items indicator
     *
     * @param int    $customerId Customer ID
     * @param array  $products   Distinct list of previously ordered products
     * @param object $dbObj      Database Object
     *
     * @return string
     */
    public static function getReorderItemsIndicator($customerId, $products, $dbObj)
    {
        $productIds = self::getCustomerOrderedProductIds($customerId, $dbObj);

        // We use the native method to extract product id from the
        // complex id/option/value
        $orderProductIds = array_map(
            'zen_get_prid',
            array_column($products, 'id')
        );

        foreach ($productIds as $productId) {
            if (in_array($productId['product_id'], $orderProductIds)) {
                return ReorderItemIndicators::REORDERED;
            }
        }

        return ReorderItemIndicators::FIRST_TIME;
    }

    /**
     * Get account update indicator
     *
     * @param array $customerInfo Customer's information
     *
     * @return string
     */
    public static function getUpdateIndicator($customerInfo)
    {
        $indicatorClass = UpdateIndicators::class;
        $dateToCheck    = $customerInfo['date_account_last_modified'];

        return self::getIndicatorValue($dateToCheck, $indicatorClass);
    }

    /**
     * Find first customer's order date
     *
     * @param array $customerOrders All customer's orders
     *
     * @return string
     */
    public static function findFirstCustomerOrderDate($customerOrders)
    {
        $orderDate = (new DateTime())->format(self::ZENCART_DATETIME_FORMAT);

        if (is_array($customerOrders) and count($customerOrders) > 0) {
            $orderDate = $customerOrders[0]['date_purchased'];
        }

        return $orderDate;
    }

    /**
     * Get Password change indicator
     *
     * @param string $date Customer's password last change date
     *
     * @return string
     */
    public static function getPasswordChangeIndicator($date)
    {
        return self::getIndicatorValue($date, PasswordChangeIndicators::class);
    }

    /**
     * Get customer's registration indicator
     *
     * @param array $customerOrders List of all customer's orders
     *
     * @return string
     */
    public static function getRegistrationIndicator($customerOrders)
    {
        $indicatorClass = RegistrationIndicators::class;
        $customerFirstOrderDate = self::findFirstCustomerOrderDate(
            $customerOrders
        );

        return self::getIndicatorValue($customerFirstOrderDate, $indicatorClass);
    }

    /**
     * Get date when sipping address is used for the first time
     *
     * @param array $orderInfo      Current order info
     * @param array $customerOrders List of all customer's orders
     *
     * @return string
     */
    public static function findShippingAddressDateFirstUsed(
        $orderInfo,
        $customerOrders
    ) {
        $cartShippingAddress = [
            "$orderInfo[firstname] $orderInfo[lastname]",
            $orderInfo['street_address'],
            $orderInfo['suburb'],
            (!empty($orderInfo['delivery_city'])) ? $orderInfo['delivery_city'] : '',
            (!empty($orderInfo['delivery_postcode'])) ? $orderInfo['delivery_postcode'] : '',
            $orderInfo['country']['title'],
        ];

        if (is_array($customerOrders) && count($customerOrders) > 0) {
            foreach ($customerOrders as $customerOrder) {
                $orderShippingAddress = [
                    $customerOrder['delivery_name'],
                    $customerOrder['delivery_street_address'],
                    $customerOrder['delivery_suburb'],
                    $customerOrder['delivery_city'],
                    $customerOrder['delivery_postcode'],
                    $customerOrder['delivery_country'],
                ];

                if (
                    count(
                        array_diff(
                            $cartShippingAddress,
                            $orderShippingAddress
                        )
                    ) === 0
                ) {
                    return $customerOrder['date_purchased'];
                }
            }
        }

        return (new DateTime())->format(self::ZENCART_DATETIME_FORMAT);
    }

    /**
     * Get shipping address usage indicator
     *
     * @param string $date Shipping address change date
     *
     * @return string
     */
    public static function getShippingAddressUsageIndicator($date)
    {
        return self::getIndicatorValue(
            $date,
            ShippingAddressUsageIndicators::class
        );
    }

    /**
     * Check if the addresses are same
     *
     * @param array $invoiceAddress  Billing address
     * @param array $shippingAddress Shipping address
     *
     * @return bool
     */
    private static function areAddressesSame($invoiceAddress, $shippingAddress)
    {
        $invoice = [
            $invoiceAddress['firstname'],
            $invoiceAddress['lastname'],
            $invoiceAddress['street_address'],
            $invoiceAddress['suburb'],
            $invoiceAddress['postcode'],
            $invoiceAddress['city'],
            $invoiceAddress['country']['id'],
        ];

        $shipping = [
            $shippingAddress['firstname'],
            $shippingAddress['lastname'],
            $shippingAddress['street_address'],
            $shippingAddress['suburb'],
            $shippingAddress['postcode'],
            $shippingAddress['city'],
            $shippingAddress['country']['id'],
        ];

        return count(array_diff($invoice, $shipping)) === 0;
    }

    /**
     * Get indicator value according the given period of time
     *
     * @param string $date           Date
     * @param string $indicatorClass Indicator class
     *
     * @return string
     */
    private static function getIndicatorValue($date, $indicatorClass)
    {
        switch (self::getDateIndicator($date)) {
            case static::LESS_THAN_30_DAYS_INDICATOR:
                return $indicatorClass::LESS_THAN_30DAYS;
            case static::MORE_THAN_30_LESS_THAN_60_INDICATOR:
                return $indicatorClass::FROM_30_TO_60_DAYS;
            case static::MORE_THAN_60_DAYS_INDICATOR:
                return $indicatorClass::MORE_THAN_60DAYS;
            default:
                if ($indicatorClass === PasswordChangeIndicators::class) {
                    return $indicatorClass::DURING_TRANSACTION;
                }

                return $indicatorClass::CURRENT_TRANSACTION;
        }
    }

    /**
     * Check if date is less than 30, between 30 and 60 or more than 60 days
     *
     * @param string $date Date
     *
     * @return string
     */
    private static function getDateIndicator($date)
    {
        $now = new DateTime();
        $checkDate = DateTime::createFromFormat(
            self::ZENCART_DATETIME_FORMAT,
            $date
        );
        $days = $checkDate->diff($now)->days;

        if ($days < 1) {
            return self::CURRENT_TRANSACTION_INDICATOR;
        }
        if ($days <= 30) {
            return self::LESS_THAN_30_DAYS_INDICATOR;
        }
        if ($days < 60) {
            return self::MORE_THAN_30_LESS_THAN_60_INDICATOR;
        }

        return self::MORE_THAN_60_DAYS_INDICATOR;
    }

    /**
     * Get list of ids of customer's ordered products
     *
     * @param int    $customerId Customer ID
     * @param object $dbObj      Database Object
     *
     * @return object
     */
    private static function getCustomerOrderedProductIds($customerId, $dbObj)
    {
        $productIdsQueryRaw = sprintf(
            "SELECT DISTINCT (op.products_id) AS product_id
            FROM %s o
            JOIN %s op ON op.orders_id = o.orders_id 
            WHERE o.customers_id = :customers_id AND o.payment_module_code = '%s'
            ORDER BY op.products_id
            ",
            TABLE_ORDERS,
            TABLE_ORDERS_PRODUCTS,
            ECOMPROCESSING_CHECKOUT_CODE
        );

        return $dbObj->Execute(
            $dbObj->bindVars(
                $productIdsQueryRaw,
                ':customers_id',
                $customerId,
                'integer'
            ),
            false, // zf_limit
            false, // zf_cache
            0,     // zf_cachetime
            true   // remove_from_queryCache
        );
    }
}
