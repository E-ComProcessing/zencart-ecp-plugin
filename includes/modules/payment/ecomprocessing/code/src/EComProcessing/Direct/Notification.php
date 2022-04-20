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

namespace EComprocessing\Direct;

use \EComprocessing\Common as EComprocessingCommon;
use \EComprocessing\Direct\Settings as EComprocessingDirectSettings;
use \EComprocessing\Direct\Transaction as EComprocessingDirectTransaction;
use \EComprocessing\Direct\TransactionProcess as EComprocessingDirectTransactionProcess;

class Notification extends \EComprocessing\Base\Notification
{
    /**
     * ModuleCode, used for redirections and loading files
     * @var string
     */
    protected static $module_code = ECOMPROCESSING_DIRECT_CODE;

    /**
     * Process Genesis Notification
     * @param array $requestData
     */
    protected static function processNotification($requestData)
    {
        global $db;

        if (!EComprocessingDirectSettings::getStatus()) {
            exit(0);
        }

        parent::processNotification($requestData);
        EComprocessingDirectTransactionProcess::bootstrap();

        try {
            $notification = new \Genesis\API\Notification($requestData);

            if ($notification->isAuthentic()) {
                $notification->initReconciliation();

                $reconcile = $notification->getReconciliationObject();
                $timestamp = EComprocessingCommon::formatTimeStamp($reconcile->timestamp);


                $data = array(
                    'unique_id'         => $reconcile->unique_id,
                    'type'              => $reconcile->transaction_type,
                    'mode'              => $reconcile->mode,
                    'status'            => $reconcile->status,
                    'currency'          => $reconcile->currency,
                    'amount'            => $reconcile->amount,
                    'timestamp'         => $timestamp,
                    'terminal_token'    => isset($reconcile->terminal_token) ? $reconcile->terminal_token : '',
                    'message'           => isset($reconcile->message) ? $reconcile->message : '',
                    'technical_message' => isset($reconcile->technical_message) ? $reconcile->technical_message : '',
                );

                EComprocessingDirectTransaction::populateTransaction($data);

                $order_id = EComprocessingDirectTransaction::getOrderByTransaction(
                    $data['unique_id']
                );

                $orderQuery = $db->Execute("SELECT
                                              `orders_id`, `orders_status`, `currency`, `currency_value`
                                            FROM " . TABLE_ORDERS . "
                                            WHERE `orders_id` = '" . abs(intval($order_id)) . "'");

                if ($orderQuery->RecordCount() < 1) {
                    exit(0);
                }

                $order = $orderQuery->fields;

                switch ($reconcile->status) {
                    case \Genesis\API\Constants\Transaction\States::APPROVED:
                        $order_status_id = EComprocessingDirectSettings::getProcessedOrderStatusID();
                        break;
                    case \Genesis\API\Constants\Transaction\States::ERROR:
                    case \Genesis\API\Constants\Transaction\States::DECLINED:
                        $order_status_id = EComprocessingDirectSettings::getFailedOrderStatusID();
                        break;
                    default:
                        $order_status_id = EComprocessingDirectSettings::getOrderStatusID();
                }

                EComprocessingDirectTransaction::setOrderStatus(
                    $order['orders_id'],
                    $order_status_id
                );

                EComprocessingDirectTransaction::performOrderStatusHistory(
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

                $notification->renderResponse();
            }
        } catch (\Exception $e) {
            //hide all exceptions
        }

        exit(0);
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
                FILENAME_EMECHANTPAY_DIRECT_IPN,
                "return=$action",
                "SSL",
                false
            )
        );
    }

}
