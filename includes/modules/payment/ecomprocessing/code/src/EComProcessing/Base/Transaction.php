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

namespace EComProcessing\Base;

class Transaction
{

    /**
     * Transaction DatabaseTableName
     * @var string
     */
    static protected $table_name = null;

    /**
     * Get Order Id By Genesis Unique Id
     * @param string $unique_id Genesis Unique Id
     * @return mixed bool on failed, int on success
     */
    public static function getOrderByTransaction($unique_id)
    {
        global $db;

        $query = $db->Execute('select `order_id` from `' . static::$table_name . '`
                                where `unique_id` = "' . $unique_id . '"');

        if ($query->RecordCount() == 1) {
            return $query->fields['order_id'];
        } else {
            return null;
        }
    }

    /**
     * Get saved transaction by id
     *
     * @param string $reference_id UniqueId of the transaction
     *
     * @return mixed bool on fail, row on success
     */
    public static function getTransactionById($unique_id)
    {
        global $db;

        $query = $db->Execute("SELECT * FROM `" . static::$table_name . "`
                               WHERE `unique_id` = '" . $unique_id . "' LIMIT 1");

        if ($query->RecordCount() > 0) {
            return $query->fields;
        }

        return false;
    }

    /**
     * Get the sum of the ammount for a list of transaction types and status
     * @param int $order_id
     * @param string $reference_id
     * @param array $types
     * @param string $status
     * @return float
     */
    public static function getTransactionsSumAmount($order_id, $reference_id, $types, $status)
    {
        $transactions = static::getTransactionsByTypeAndStatus($order_id, $reference_id, $types, $status);
        $totalAmount = 0;

        /** @var $transaction */
        foreach ($transactions as $transaction) {
            $totalAmount +=  $transaction['amount'];
        }

        return $totalAmount;
    }

    /**
     * Get the detailed transactions list of an order for transaction types and status
     * @param int $order_id
     * @param string $reference_id
     * @param array $transaction_types
     * @param string $status
     * @return array
     */
    public static function getTransactionsByTypeAndStatus($order_id, $reference_id, $transaction_types, $status)
    {
        global $db;

        $query = $db->Execute("SELECT
                                  *
                                FROM `" . static::$table_name . "` as t
                                WHERE (t.`order_id` = '" . abs(intval($order_id)) . "') and " .
            (!empty($reference_id)
                ? " (t.`reference_id` = '" . $reference_id . "') and "
                : "") . "
                (t.`type` in ('" .
            (is_array($transaction_types)
                ? implode("','", $transaction_types)
                : $transaction_types) . "')) and
                (t.`status` = '" . $status . "')
            ");

        if ($query->RecordCount() > 0) {
            $transactions = array();

            while (!$query->EOF) {
                $transactions[] = $query->fields;
                $query->MoveNext();
            }
            return $transactions;
        }

        return false;

    }

    /**
     * Get saved transactions by order id
     *
     * @param int $order_id OrderId
     *
     * @return mixed bool on fail, rows on success
     */
    public static function getTransactionsByOrder($order_id)
    {
        global $db;

        $query = $db->Execute("SELECT * FROM `" . static::$table_name . "`
                               WHERE `order_id` = '" . abs(intval($order_id)) . "'");

        if ($query->RecordCount() > 0) {
            $transactions = array();

            while (!$query->EOF) {
                $transactions[] = $query->fields;
                $query->MoveNext();
            }
        }

        return false;
    }

    /**
     * Add transaction to the database
     *
     * @param array $data
     */
    private static function addTransaction($data)
    {
        global $db;

        try {
            $fields = implode(', ', array_map(
                function ($v, $k) {
                    return sprintf('`%s`', $k);
                },
                $data,
                array_keys(
                    $data
                )
            ));

            $values = implode(', ', array_map(
                function ($v) {
                    return sprintf("'%s'", $v);
                },
                $data,
                array_keys(
                    $data
                )
            ));

            $db->Execute("
				INSERT INTO
					`" . static::$table_name . "` (" . $fields . ")
				VALUES
					(" . $values . ")
			");
        } catch (\Exception $exception) {
            //$this->logEx($exception);
        }
    }

    /**
     * Update existing transaction in the database
     *
     * @param array $data
     */
    private static function updateTransaction($data)
    {
        global $db;

        try {
            $fields = implode(', ', array_map(
                function ($v, $k) {
                    return sprintf("`%s` = '%s'", $k, $v);
                },
                $data,
                array_keys(
                    $data
                )
            ));

            $db->Execute("
				UPDATE
					`" . static::$table_name . "`
				SET
					" . $fields . "
				WHERE
				    `unique_id` = '" . $data['unique_id'] . "'
			");
        } catch (\Exception $exception) {
            //$this->logEx($exception);
        }
    }

    /**
     * Sanitize transaction data and check
     * whether an UPDATE or INSERT is required
     *
     * @param array $data
     */
    public static function populateTransaction($data = array())
    {
        global $db;

        try {
            // Check if transaction exists
            $insertQuery = $db->Execute("
                SELECT
                    *
                FROM
                    `" . static::$table_name . "`
                WHERE
                    `unique_id` = '" . $data['unique_id'] . "'
            ");

            if ($insertQuery->RecordCount() > 0) {
                static::updateTransaction($data);
            } else {
                static::addTransaction($data);
            }
        } catch (\Exception $exception) {
            //$this->logEx($exception);
        }
    }

    /**
     * Save Order Status History to the database
     * @param array $data
     */
    public static function performOrderStatusHistory($data)
    {
        $sql_data_array = array(
            'orders_id'         => $data['orders_id'],
            'orders_status_id'  => $data['order_status_id'],
            'date_added'        => 'now()',
            'customer_notified' => '1',
            'comments'          =>
                sprintf(
                    "[{$data['type']}]" .  PHP_EOL .
                    "- Unique ID: %s" . PHP_EOL .
                    "- Status: %s".     PHP_EOL .
                    "- Message: %s",
                    $data['payment']['unique_id'],
                    $data['payment']['status'],
                    $data['payment']['message']
                ),
        );

        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
    }

    /**
     * Determine if transaction can be captured
     * @param array $transaction
     * @return bool
     */
    public static function getCanCaptureTransaction($transaction)
    {
        return (in_array($transaction['type'], array(
                    \Genesis\API\Constants\Transaction\Types::AUTHORIZE,
                    \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D
                )) &&
                ($transaction['status'] ==
                    \Genesis\API\Constants\Transaction\States::APPROVED)
        );
    }

    /**
     * Determine if transaction can be refunded
     * @param array $transaction
     * @return bool
     */
    public static function getCanRefundTransaction($transaction)
    {
        return (in_array($transaction['type'], array(
                    \Genesis\API\Constants\Transaction\Types::CAPTURE,
                    \Genesis\API\Constants\Transaction\Types::SALE,
                    \Genesis\API\Constants\Transaction\Types::SALE_3D,
                    \Genesis\API\Constants\Transaction\Types::INIT_RECURRING_SALE,
                    \Genesis\API\Constants\Transaction\Types::RECURRING_SALE
                )) &&
                ($transaction['status'] ==
                    \Genesis\API\Constants\Transaction\States::APPROVED)
        );
    }

    /**
     * Determine if transaction can be voided
     * @param array $transaction
     * @return bool
     */
    public static function getCanVoidTransaction($transaction)
    {
        return (
            $transaction['status'] == \Genesis\API\Constants\Transaction\States::APPROVED &&
            in_array($transaction['type'], array(
                \Genesis\API\Constants\Transaction\Types::AUTHORIZE,
                \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D,
                \Genesis\API\Constants\Transaction\Types::CAPTURE,
                \Genesis\API\Constants\Transaction\Types::SALE,
                \Genesis\API\Constants\Transaction\Types::SALE_3D,
                \Genesis\API\Constants\Transaction\Types::INIT_RECURRING_SALE,
                \Genesis\API\Constants\Transaction\Types::RECURRING_SALE,
                \Genesis\API\Constants\Transaction\Types::REFUND
            ))
        );
    }

    /**
     * Updates Order Status
     * @param int $orderId
     * @param int $orderStatusId
     */
    public static function setOrderStatus($orderId, $orderStatusId)
    {
        global $db;

        // Update Order Status
        $db->Execute("UPDATE " . TABLE_ORDERS . "
                      SET `orders_status` = '" . abs(intval($orderStatusId)) . "', `last_modified` = NOW()
                      WHERE `orders_id` = '" . abs(intval($orderId)) . "'");

    }
}
