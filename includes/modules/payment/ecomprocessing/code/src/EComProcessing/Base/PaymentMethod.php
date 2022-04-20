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

namespace EComprocessing\Base;

use \EComprocessing\Common as EComprocessingCommon;
use \EComprocessing\OrderTransactions as EComprocessingOrderTransactions;
use \EComprocessing\Base\Transaction as EComprocessingTransactionBase;
use \EComprocessing\Checkout\TransactionProcess as EComprocessingCheckoutTransactionProcess;

abstract class PaymentMethod extends \base
{

    const PLATFORM_TRANSACTION_PREFIX = 'zencart-';

    /**
     * $code determines the internal 'code' name used to designate "this" payment module
     *
     * @var string
     */
    public $code;
    /**
     * $version stores the current version of the module
     * @var string
     */
    public $version;
    /**
     * $title is the displayed name for this payment method
     *
     * @var string
     */
    public $title;
    /**
     * $description is a soft name for this payment method
     *
     * @var string
     */
    public $description;
    /**
     * $enabled determines whether this module shows or not... in catalog.
     *
     * @var boolean
     */
    public $enabled;
    /**
     * Sort Order of this payment option on the customer payment page
     * @var integer
     */
    public $sort_order;
    /**
     * Page to go to upon submitting page info
     * @var string
     */
    public $form_action_url;
    /**
     * Default Order Status
     * @var integer
     */
    public $order_status;
    /**
     * Used to store the Response Object, after payment is executed.
     * @var \stdClass
     */
    protected $responseObject;

    /**
     * Generate Reference Transaction (Capture, Refund, Void)
     * @param string $transaction_type
     * @param \stdClass $data
     * @return \stdClass
     */
    abstract protected function getReferenceTransactionResponse($transaction_type, $data);

    /**
     * Extends the parameters needed for displaying the admin-page components
     * @param array $data
     */
    abstract protected function extendOrderTransPanelData(&$data);

    /**
     * Store data to an existing / a new Transaction
     * @param array $data
     * @return mixed
     */
    abstract protected function doPopulateTransaction($data);

    /**
     * Save Order Status History to the database (after Capture, Refund Void)
     * @param array $data
     * @return mixed
     */
    abstract protected function doPerformOrderStatusHistory($data);

    /**
     * Used to determine the Module Transactions Table Name
     * @return string
     */
    abstract protected function getTableNameTransactions();

    /**
     * Get the sum of the ammount for a list of transaction types and status
     * @param int $order_id
     * @param string $reference_id
     * @param array $types
     * @param string $status
     * @return float
     */
    abstract protected function getTransactionsSumAmount($order_id, $reference_id, $types, $status);

    /**
     * Get the detailed transactions list of an order for transaction types and status
     * @param int $order_id
     * @param string $reference_id
     * @param array $transaction_types
     * @param string $status
     * @return array
     */
    abstract protected function getTransactionsByTypeAndStatus($order_id, $reference_id, $transaction_types, $status);

    /**
     * Get saved transaction by id
     *
     * @param string $reference_id UniqueId of the transaction
     *
     * @return mixed bool on fail, row on success
     */
    abstract protected function getTransactionById($unique_id);

    /**
     * Check to see whether module is installed
     *
     * @return boolean
     */
    abstract public function check();

    /**
     * Execute Capture / Refund / Void Transaction
     * @param string $transaction_type
     * @param integer $order_id
     */
    private function doExecuteReferenceTransaction($transaction_type, $order_id)
    {
        global $messageStack;

        try {
            $data = new \stdClass();
            $data->reference_id = $_POST['reference_id'];
            $data->usage = $_POST['message'];
            $data->remote_address = EComprocessingCommon::getServerRemoteAddress();

            if ($transaction_type != \Genesis\API\Constants\Transaction\Types::VOID) {
                $data->amount = $_POST['amount'];
            }

            $transaction = $this->getTransactionById($data->reference_id);

            if ($transaction_type != \Genesis\API\Constants\Transaction\Types::VOID) {
                $data->currency = $transaction['currency'];
            }

            $data->type  = $transaction['type'];

            $responseObject = $this->getReferenceTransactionResponse(
                $transaction_type,
                $data
            );

            if (isset($responseObject->unique_id)) {
                $timestamp = EComprocessingCommon::formatTimeStamp($responseObject->timestamp);

                if ($responseObject->status == \Genesis\API\Constants\Transaction\States::APPROVED) {
                    $messageStack->add_session($responseObject->message, 'success');
                } else {
                    $messageStack->add_session($responseObject->message, 'error');
                }

                $data = array(
                    'order_id' => $order_id,
                    'reference_id' => $transaction['unique_id'],
                    'unique_id' => $responseObject->unique_id,
                    'type' => $responseObject->transaction_type,
                    'mode' => $responseObject->mode,
                    'status' => $responseObject->status,
                    'amount' => (isset($responseObject->amount) ? $responseObject->amount : "0"),
                    'currency' => $responseObject->currency,
                    'timestamp' => $timestamp,
                    'terminal_token' =>
                        isset($responseObject->terminal_token)
                            ? $responseObject->terminal_token
                            : $transaction['terminal_token'],
                    'message' =>
                        isset($responseObject->message)
                            ? $responseObject->message
                            : '',
                    'technical_message' =>
                        isset($responseObject->technical_message)
                            ? $responseObject->technical_message
                            : '',
                );

                $this->doPerformOrderStatusHistory(
                    array(
                        'orders_id'       => $order_id,
                        'transaction_type'  => $responseObject->transaction_type,
                        'payment'         => array(
                            'unique_id'       => $responseObject->unique_id,
                            'status'          => $responseObject->status,
                            'message'         => $responseObject->message
                        )
                    )
                );

                $this->doPopulateTransaction($data);
            }
        } catch (\Exception $e) {
            $messageStack->add_session($e->getMessage(), 'error');
        }
    }

    /**
     * Registers Genesis autoload for a specific payment module.
     */
    abstract protected function registerLibraries();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->registerLibraries();
        $this->init();
    }

    /**
     * Perform Code to initialize the Payment Module
     */
    protected function init()
    {
        global $order;

        // Page to go to upon submitting page info
        $this->form_action_url = zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL', false);

        if (is_object($order)) {
            $this->update_status();
        }

        // verify table structure
        if (IS_ADMIN_FLAG === true) {
            $this->tableCheckup();
        }
    }

    /**
     * calculate zone matches and flag settings to determine whether this module should display to customers or not
     *
     */
    public function update_status()
    {
        $this->enabled = false;
    }

    /**
     * Used to perform additional checks, before the Payment is submitted
     */
    public function pre_confirmation_check()
    {

    }

    /**
     * Display Additional Information on the Checkout Confirmation Page
     *
     * @return array
     */
    public function confirmation()
    {
        return false;
    }

    /**
     * Build the data and actions to process when the "Submit" button is pressed on the order-confirmation screen.
     * This sends the data to the payment gateway for processing.
     *
     * @return string
     */
    public function process_button()
    {
        return false;
    }

    /**
     * JS validation which does error-checking of data-entry if this module is selected for use
     *
     * @return string
     */
    public function javascript_validation()
    {
    }

    /**
     * Post-process activities. Updates the order-status history data and transaction.
     * Redirects to an external url to complete the Payment if necessary
     *
     * @return boolean
     */
    public function after_process()
    {
        global $insert_id;

        if (isset($this->responseObject) && isset($this->responseObject->unique_id)) {
            $timestamp = EComprocessingCommon::formatTimeStamp($this->responseObject->timestamp);

            $data = array(
                'type' => ($this->responseObject->transaction_type ?: 'checkout'),
                'reference_id' => '0',
                'order_id' => $insert_id,
                'unique_id' => $this->responseObject->unique_id,
                'mode' => $this->responseObject->mode,
                'status' => $this->responseObject->status,
                'amount' => $this->responseObject->amount,
                'currency' => $this->responseObject->currency,
                'message' =>
                    isset($this->responseObject->message)
                        ? $this->responseObject->message
                        : '',
                'technical_message' =>
                    isset($this->responseObject->technical_message)
                        ? $this->responseObject->technical_message
                        : '',
                'timestamp' => $timestamp,
            );

            $this->doPopulateTransaction($data);

            if (isset($this->responseObject->redirect_url)) {
                zen_redirect($this->responseObject->redirect_url);
            } else {
                $this->processUpdateOrder($insert_id);
            }
        }
        return true;
    }

    /**
     * Updates Order Status and created Order Status History
     * from the Gateway Response
     * @param int $orderId
     * @return bool
     */
    protected function processUpdateOrder($orderId)
    {
        return
            isset($this->responseObject) &&
            isset($this->responseObject->status);
    }

    /**
     * Build admin-page components
     *
     * @param int $zf_order_id
     * @return string
     */
    public function admin_notification($zf_order_id)
    {
        global $db, $order;

        $data = new \stdClass;
        $data->paths = array(
            'images' => 'images/ecomprocessing/',
            'js' => 'includes/javascript/ecomprocessing/',
            'css' => 'includes/css/ecomprocessing/'
        );

        $currency = EComprocessingCommon::getZenCurrency($order->info['currency']);

        if ($currency === false) {
            return;
        }

        $data->params = array(
            'module_name' => 'ecomprocessing',
            'currency' => $currency
        );

        $this->extendOrderTransPanelData($data);

        $result = $db->Execute('SELECT *
				FROM `' . $this->getTableNameTransactions() . '`
				WHERE `order_id` = ' . $zf_order_id);
        $transactions = array();

        while (!$result->EOF) {
            $transactions[] = $result->fields;
            $result->MoveNext();
        }

        foreach ($transactions as &$transaction) {
            $transaction['timestamp'] = date('H:i:s m/d/Y', strtotime($transaction['timestamp']));

            if (EComprocessingTransactionBase::getCanCaptureTransaction($transaction)) {
                $transaction['can_capture'] = true;
            } else {
                $transaction['can_capture'] = false;
            }

            if ($transaction['can_capture']) {
                $totalAuthorizedAmount = $this->getTransactionsSumAmount(
                    $transaction['order_id'],
                    $transaction['reference_id'],
                    array(
                        \Genesis\API\Constants\Transaction\Types::AUTHORIZE,
                        \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D,
                        \Genesis\API\Constants\Transaction\Types::GOOGLE_PAY,
                        \Genesis\API\Constants\Transaction\Types::PAY_PAL,
                        \Genesis\API\Constants\Transaction\Types::APPLE_PAY,
                    ),
                    \Genesis\API\Constants\Transaction\States::APPROVED
                );
                $totalCapturedAmount = $this->getTransactionsSumAmount(
                    $transaction['order_id'],
                    $transaction['unique_id'],
                    \Genesis\API\Constants\Transaction\Types::CAPTURE,
                    \Genesis\API\Constants\Transaction\States::APPROVED
                );
                $transaction['available_amount'] = $totalAuthorizedAmount - $totalCapturedAmount;
            }

            if (EComprocessingTransactionBase::getCanRefundTransaction($transaction)) {
                $transaction['can_refund'] = true;
            } else {
                $transaction['can_refund'] = false;
            }

            if ($transaction['can_refund']) {
                $totalCapturedAmount = $transaction['amount'];
                $totalRefundedAmount = $this->getTransactionsSumAmount(
                    $transaction['order_id'],
                    $transaction['unique_id'],
                    \Genesis\API\Constants\Transaction\Types::REFUND,
                    \Genesis\API\Constants\Transaction\States::APPROVED
                );
                $transaction['available_amount'] = $totalCapturedAmount - $totalRefundedAmount;
            }

            if (EComprocessingTransactionBase::getCanVoidTransaction($transaction)) {
                $transaction['can_void'] = true;
                $transaction['void_exists'] = $this->getTransactionsByTypeAndStatus(
                    $transaction['order_id'],
                    $transaction['unique_id'],
                    \Genesis\API\Constants\Transaction\Types::VOID,
                    \Genesis\API\Constants\Transaction\States::APPROVED
                ) !== false;
            } else {
                $transaction['can_void'] = false;
            }

            if (!isset($transaction['available_amount'])) {
                $transaction['available_amount'] = $transaction['amount'];
            }

            $transaction['amount'] = EComprocessingCommon::formatTransactionValue(
                $transaction['amount'],
                $currency
            );

            $transaction['available_amount'] = EComprocessingCommon::formatTransactionValue(
                $transaction['available_amount'],
                $currency
            );
        }

        // Sort the transactions list in the following order:
        //
        // 1. Sort by timestamp (date), i.e. most-recent transactions on top
        // 2. Sort by relations, i.e. every parent has the child nodes immediately after

        // Ascending Date/Timestamp sorting
        uasort($transactions, function ($a, $b) {
            // sort by timestamp (date) first
            if (@$a["timestamp"] == @$b["timestamp"]) {
                return 0;
            }

            return (@$a["timestamp"] > @$b["timestamp"]) ? 1 : -1;
        });

        // Create the parent/child relations from a flat array
        $array_asc = array();

        foreach ($transactions as $key => $val) {
            // create an array with ids as keys and children
            // with the assumption that parents are created earlier.
            // store the original key
            if (isset($array_asc[$val['unique_id']])) {
                $array_asc[$val['unique_id']]['org_key'] = $key;

                $array_asc[$val['unique_id']] = array_merge($val, $array_asc[$val['unique_id']]);
            } else {
                $array_asc[$val['unique_id']] = array_merge($val, array('org_key' => $key));
            }

            if ($val['reference_id']) {
                $array_asc[$val['reference_id']]['children'][] = $val['unique_id'];
            }
        }

        // Order the parent/child entries
        $transactions = array();

        foreach ($array_asc as $val) {
            if (isset($val['reference_id']) && $val['reference_id']) {
                continue;
            }

            EComprocessingCommon::sortTransactionByRelation($transactions, $val, $array_asc);
        }

        $data->transactions = $transactions;

        return EComprocessingOrderTransactions::printOrderTransactions($data);
    }

    /**
     * Used to display error message details
     *
     * @return array
     */
    public function get_error()
    {
    }

    /**
     * Check and fix table structure if appropriate
     */
    public function tableCheckup()
    {
    }

    /**
     * Used to submit a refund for a given transaction.
     * @param integer $order_id
     * @return bool
     */
    public function _doRefund($order_id)
    {
        $this->doExecuteReferenceTransaction(
            \Genesis\API\Constants\Transaction\Types::REFUND,
            $order_id
        );
        return true;
    }

    /**
     * Used to capture part or all of a given previously-authorized transaction.
     * @param integer $order_id
     * @return bool
     */
    public function _doCapt($order_id)
    {
        $this->doExecuteReferenceTransaction(
            \Genesis\API\Constants\Transaction\Types::CAPTURE,
            $order_id
        );
        return true;
    }

    /**
     * Used to void a given previously-authorized transaction.
     * @param integer $order_id
     * @return bool
     */
    public function _doVoid($order_id)
    {
        $this->doExecuteReferenceTransaction(
            \Genesis\API\Constants\Transaction\Types::VOID,
            $order_id
        );
        return true;
    }

    /**
     * Used to generate transaction id to initial transaction
     *
     * @param string $prefix Custom to transaction id
     *
     * @return string
     */
    public static function generateTransactionId($prefix = '')
    {
        return (string)$prefix . md5(uniqid() . microtime(true));
    }
}
