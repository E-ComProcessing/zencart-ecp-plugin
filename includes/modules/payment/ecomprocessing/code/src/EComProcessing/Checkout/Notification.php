<?php
/*
 * Copyright (C) 2016 E-ComProcessing™
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
 * @author      E-ComProcessing
 * @copyright   2016 E-ComProcessing Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace EComProcessing\Checkout;

use \EComProcessing\Checkout\Settings as EComProcessingCheckoutSettings;
use \EComProcessing\Common as EComProcessingCommon;
use \EComProcessing\Checkout\Transaction as EComProcessingCheckoutTransaction;
use \EComProcessing\Checkout\TransactionProcess as EComProcessingCheckoutTransactionProcess;

class Notification extends \EComProcessing\Base\Notification
{
    /**
     * ModuleCode, used for redirections and loading files
     * @var string
     */
    protected static $module_code = ECOMPROCESSING_CHECKOUT_CODE;

    /**
     * Process Genesis Notification
     * @param array $requestData
     */
    protected static function processNotification($requestData)
    {
        global $db;

        if (!EComProcessingCheckoutSettings::getStatus()) {
            exit(0);
        }

        parent::processNotification($requestData);
        EComProcessingCheckoutTransactionProcess::bootstrap();

        try {
            $notification = new \Genesis\API\Notification($requestData);

            if ($notification->isAuthentic()) {
                $notification->initReconciliation();

                $reconcile = $notification->getReconciliationObject();
                $timestamp = EComProcessingCommon::formatTimeStamp($reconcile->timestamp);

                $data = array(
                    'unique_id' => $reconcile->unique_id,
                    'status'    => $reconcile->status,
                    'currency'  => $reconcile->currency,
                    'amount'    => $reconcile->amount,
                    'timestamp' => $timestamp,
                );

                EComProcessingCheckoutTransaction::populateTransaction($data);


                if (isset($reconcile->payment_transaction)) {
                    $payment = $reconcile->payment_transaction;

                    $timestamp = EComProcessingCommon::formatTimeStamp($payment->timestamp);

                    $order_id = EComProcessingCheckoutTransaction:: getOrderByTransaction($reconcile->unique_id);

                    $data = array(
                        'order_id'          => $order_id,
                        'reference_id'      => $reconcile->unique_id,
                        'unique_id'         => $payment->unique_id,
                        'type'              => $payment->transaction_type,
                        'mode'              => $payment->mode,
                        'status'            => $payment->status,
                        'currency'          => $payment->currency,
                        'amount'            => $payment->amount,
                        'timestamp'         => $timestamp,
                        'terminal_token'    => isset($payment->terminal_token) ? $payment->terminal_token : '',
                        'message'           => isset($payment->message) ? $payment->message : '',
                        'technical_message' => isset($payment->technical_message) ? $payment->technical_message : '',
                    );

                    EComProcessingCheckoutTransaction::populateTransaction($data);

                    $orderQuery = $db->Execute("SELECT
                                                  `orders_id`, `orders_status`, `currency`, `currency_value`
                                                FROM " . TABLE_ORDERS . "
                                                WHERE `orders_id` = '" . abs(intval($order_id)) . "'");

                    if ($orderQuery->RecordCount() < 1) {
                        exit(0);
                    }

                    $order = $orderQuery->fields;

                    switch ($payment->status) {
                        case \Genesis\API\Constants\Transaction\States::APPROVED:
                            $order_status_id = EComProcessingCheckoutSettings::getProcessedOrderStatusID();
                            break;
                        case \Genesis\API\Constants\Transaction\States::ERROR:
                        case \Genesis\API\Constants\Transaction\States::DECLINED:
                            $order_status_id = EComProcessingCheckoutSettings::getFailedOrderStatusID();
                            break;
                        default:
                            $order_status_id = EComProcessingCheckoutSettings::getOrderStatusID();
                    }

                    EComProcessingCheckoutTransaction::setOrderStatus(
                        $order['orders_id'],
                        $order_status_id
                    );

                    EComProcessingCheckoutTransaction::performOrderStatusHistory(
                        array(
                            'type'            => 'Notification',
                            'orders_id'       => $order['orders_id'],
                            'order_status_id' => $order_status_id,
                            'payment'         => array(
                                'unique_id' => $payment->unique_id,
                                'status'    => $payment->status,
                                'message'   => $payment->message
                            )
                        )
                    );
                } else {
                    $order_id = EComProcessingCheckoutTransaction::getOrderByTransaction($reconcile->unique_id);

                    $orderQuery = $db->Execute("SELECT
                                                  `orders_id`, `orders_status`, `currency`, `currency_value`
                                                FROM " . TABLE_ORDERS . "
                                                WHERE `orders_id` = '" . abs(intval($order_id)) . "'");

                    if ($orderQuery->RecordCount() < 1) {
                        exit(0);
                    }

                    $order = $orderQuery->fields;

                    $order_status_id = EComProcessingCheckoutSettings::getFailedOrderStatusID();

                    EComProcessingCheckoutTransaction::setOrderStatus(
                        $order['orders_id'],
                        $order_status_id
                    );

                    EComProcessingCheckoutTransaction::performOrderStatusHistory(
                        array(
                            'type'            => 'Notification',
                            'orders_id'       => $order['orders_id'],
                            'order_status_id' => $order_status_id,
                            'payment'         => array(
                                'unique_id' => $reconcile->unique_id,
                                'status'    => $reconcile->status,
                                'message'   => $reconcile->message
                            )
                        )
                    );
                }

                $notification->renderResponse();
            }
        } catch (\Exception $e) {
            //hide all exceptions
        }

        exit(0);
    }

    /**
     * Process Return Action
     * @param string $action
     * @return void
     */
    protected static function processReturnAction($action)
    {
        switch ($action) {
            case static::ACTION_CANCEL:
                if (isset($_SESSION['order_summary']) && isset($_SESSION['order_summary']['order_number'])) {
                    EComProcessingCheckoutTransaction::setOrderStatus(
                        $_SESSION['order_summary']['order_number'],
                        EComProcessingCheckoutSettings::getCanceledOrderStatusID()
                    );
                }
                break;
        }

        parent::processReturnAction($action);
    }

    /**
     * Build Return URL from Genesis
     * @param string $action
     * @return string
     */
    public static function buildReturnURL($action)
    {
        return html_entity_decode(
            zen_href_link(
                FILENAME_EMECHANTPAY_CHECKOUT_IPN,
                "return=$action",
                "SSL",
                false
            )
        );
    }
}
