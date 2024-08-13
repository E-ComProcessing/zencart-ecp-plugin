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

use Ecomprocessing\Checkout\Settings as EcomprocessingCheckoutSettings;
use Ecomprocessing\Common as EcomprocessingCommon;
use Ecomprocessing\Checkout\Transaction as EcomprocessingCheckoutTransaction;
use Ecomprocessing\Checkout\TransactionProcess as EcomprocessingCheckoutTransactionProcess;
use Ecomprocessing\Helpers\SessionHelper;
use Genesis\Api\Notification as EcomprocessingNotification;

class Notification extends \Ecomprocessing\Base\Notification
{
    /**
     * ModuleCode, used for redirections and loading files
     * @var string
     */
    protected static $module_code = ECOMPROCESSING_CHECKOUT_CODE;

    /**
     * Process Genesis Notification
     * @param array $requestData
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    protected static function processNotification($requestData)
    {
        global $db;

        if (!EcomprocessingCheckoutSettings::isEnabled()) {
            exit('EMP plugin disabled');
        }

        parent::processNotification($requestData);
        EcomprocessingCheckoutTransactionProcess::bootstrap();

        try {
            $notification = new EcomprocessingNotification($requestData);

            if ($notification->isAuthentic()) {
                $notification->initReconciliation();

                $reconcile = $notification->getReconciliationObject();
                $timestamp = EcomprocessingCommon::formatTimeStamp($reconcile->timestamp);

                $data = array(
                    'unique_id' => $reconcile->unique_id,
                    'status'    => $reconcile->status,
                    'currency'  => $reconcile->currency,
                    'amount'    => $reconcile->amount,
                    'timestamp' => $timestamp,
                );

                EcomprocessingCheckoutTransaction::populateTransaction($data);


                if (isset($reconcile->payment_transaction)) {
                    $payment = $reconcile->payment_transaction;

                    $timestamp = EcomprocessingCommon::formatTimeStamp($payment->timestamp);

                    $order_id = EcomprocessingCheckoutTransaction:: getOrderByTransaction($reconcile->unique_id);

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

                    EcomprocessingCheckoutTransaction::populateTransaction($data);

                    $orderQuery = $db->Execute("SELECT
                                                  `orders_id`, `orders_status`, `currency`, `currency_value`
                                                FROM " . TABLE_ORDERS . "
                                                WHERE `orders_id` = '" . abs(intval($order_id)) . "'");

                    if ($orderQuery->RecordCount() < 1) {
                        exit('Unknown order_id');
                    }

                    $order = $orderQuery->fields;

                    switch ($payment->status) {
                        case \Genesis\Api\Constants\Transaction\States::APPROVED:
                            $order_status_id = EcomprocessingCheckoutSettings::getProcessedOrderStatusID();
                            break;
                        case \Genesis\Api\Constants\Transaction\States::ERROR:
                        case \Genesis\Api\Constants\Transaction\States::DECLINED:
                            $order_status_id = EcomprocessingCheckoutSettings::getFailedOrderStatusID();
                            break;
                        default:
                            $order_status_id = EcomprocessingCheckoutSettings::getOrderStatusID();
                    }

                    EcomprocessingCheckoutTransaction::setOrderStatus(
                        $order['orders_id'],
                        $order_status_id
                    );

                    EcomprocessingCheckoutTransaction::performOrderStatusHistory(
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
                    $order_id = EcomprocessingCheckoutTransaction::getOrderByTransaction($reconcile->unique_id);

                    $orderQuery = $db->Execute("SELECT
                                                  `orders_id`, `orders_status`, `currency`, `currency_value`
                                                FROM " . TABLE_ORDERS . "
                                                WHERE `orders_id` = '" . abs(intval($order_id)) . "'");

                    if ($orderQuery->RecordCount() < 1) {
                        exit('Unknown order_id (reconcile)');
                    }

                    $order = $orderQuery->fields;

                    $order_status_id = EcomprocessingCheckoutSettings::getFailedOrderStatusID();

                    EcomprocessingCheckoutTransaction::setOrderStatus(
                        $order['orders_id'],
                        $order_status_id
                    );

                    EcomprocessingCheckoutTransaction::performOrderStatusHistory(
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
            exit("Exception Notification: {$e->getMessage()}");
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
                $order_summary = SessionHelper::get('order_summary');
                if ($order_summary && isset($order_summary['order_number'])) {
                    EcomprocessingCheckoutTransaction::setOrderStatus(
                        $order_summary['order_number'],
                        EcomprocessingCheckoutSettings::getCanceledOrderStatusID()
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
                FILENAME_ECOMPROCESSING_CHECKOUT_IPN,
                "return=$action",
                "SSL",
                false
            )
        );
    }
}
