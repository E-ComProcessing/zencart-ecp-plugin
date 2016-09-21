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

require DIR_FS_CATALOG . DIR_WS_INCLUDES . "modules/payment/ecomprocessing/code/vendor/autoload.php";

use \EComProcessing\Checkout\Installer          as EComProcessingCheckoutInstaller;
use \EComProcessing\Checkout\Settings           as EComProcessingCheckoutSettings;
use \EComProcessing\Checkout\Transaction        as EComProcessingCheckoutTransaction;
use \EComProcessing\Checkout\TransactionProcess as EComProcessingCheckoutTransactionProcess;
use \EComProcessing\Checkout\Notification       as EComProcessingCheckoutNotification;

class ecomprocessing_checkout extends \EComProcessing\Base\PaymentMethod
{

    /**
     * Generate Reference Transaction (Capture, Refund, Void)
     * @param string $transaction_type
     * @param stdClass $data
     * @return stdClass
     */
    protected function getReferenceTransactionResponse($transaction_type, $data)
    {
        return EComProcessingCheckoutTransactionProcess::$transaction_type($data);
    }

    /**
     * Extends the parameters needed for displaying the admin-page components
     * @param array $data
     */
    protected function extendOrderTransPanelData(&$data)
    {
        $data->params['modal'] = array(
            'capture' => array(
                'allowed' => EComProcessingCheckoutSettings::getIsPartialCaptureAllowed(),
                'form' => array(
                    'action' => 'doCapture',
                ),
                'input' => array(
                    'visible' => true,
                )
            ),
            'refund' => array(
                'allowed' => EComProcessingCheckoutSettings::getIsPartialRefundAllowed(),
                'form' => array(
                    'action' => 'doRefund',
                ),
                'input' => array(
                    'visible' => true,
                )
            ),
            'void' => array(
                'allowed' => EComProcessingCheckoutSettings::getIsVoidTransactionAllowed(),
                'form' => array(
                    'action' => 'doVoid',
                ),
                'input' => array(
                    'visible' => false,
                )
            )
        );

        $data->translations = array(
            'panel' => array(
                'title' =>
                    MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_ORDER_TRANS_TITLE,
                'transactions' => array(
                    'header' => array(
                        'id' =>
                            MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_ORDER_TRANS_HEADER_ID,
                        'type' =>
                            MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_ORDER_TRANS_HEADER_TYPE,
                        'timestamp' =>
                            MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_ORDER_TRANS_HEADER_TIMESTAMP,
                        'amount' =>
                            MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_ORDER_TRANS_HEADER_AMOUNT,
                        'status' =>
                            MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_ORDER_TRANS_HEADER_STATUS,
                        'message' =>
                            MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_ORDER_TRANS_HEADER_MESSAGE,
                        'mode' =>
                            MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_ORDER_TRANS_HEADER_MODE,
                        'action_capture' =>
                            MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_ORDER_TRANS_HEADER_ACTION_CAPTURE,
                        'action_refund' =>
                            MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_ORDER_TRANS_HEADER_ACTION_REFUND,
                        'action_void' =>
                            MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_ORDER_TRANS_HEADER_ACTION_VOID
                    )
                )
            ),
            'modal' => array(
                'capture' => array(
                    'title' =>
                        MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_CAPTURE_TRAN_TITLE,
                    'input' => array(
                        'label' =>
                            MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_ORDER_TRANS_MODAL_AMOUNT_LABEL_CAPTURE,
                        'warning_tooltip' =>
                            MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_MESSAGE_CAPTURE_PARTIAL_DENIED
                    ),
                    'buttons' => array(
                        'submit' => array(
                            'title' =>
                                MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_BUTTON_CAPTURE
                        ),
                        'cancel' => array(
                            'title' =>
                                MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_BUTTON_CANCEL
                        )
                    )
                ),
                'refund' => array(
                    'title' =>
                        MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_REFUND_TRAN_TITLE,
                    'input' => array(
                        'label' =>
                            MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_ORDER_TRANS_MODAL_AMOUNT_LABEL_REFUND,
                        'warning_tooltip' =>
                            MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_MESSAGE_REFUND_PARTIAL_DENIED
                    ),
                    'buttons' => array(
                        'submit' => array(
                            'title' =>
                                MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_BUTTON_REFUND
                        ),
                        'cancel' => array(
                            'title' =>
                                MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_BUTTON_CANCEL
                        )
                    )
                ),
                'void' => array(
                    'title' =>
                        MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_VOID_TRAN_TITLE,
                    'input' => array(
                        'label' => null,
                        'warning_tooltip' =>
                            MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_MESSAGE_VOID_DENIED
                    ),
                    'buttons' => array(
                        'submit' => array(
                            'title' =>
                                MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_BUTTON_VOID
                        ),
                        'cancel' => array(
                            'title' =>
                                MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_LABEL_BUTTON_CANCEL
                        )
                    )
                )
            )
        );
    }

    /**
     * Store data to an existing / a new Transaction
     * @array $data
     * @return mixed
     */
    protected function doPopulateTransaction($data)
    {
        EComProcessingCheckoutTransaction::populateTransaction($data);
    }

    /**
     * Save Order Status History to the database (after Capture, Refund Void)
     * @param array $data
     * @return mixed
     */
    protected function doPerformOrderStatusHistory($data)
    {
        switch ($data['transaction_type']) {
            case \Genesis\API\Constants\Transaction\Types::CAPTURE:
                $data['type'] = 'Captured';
                $data['order_status_id'] = EComProcessingCheckoutSettings::getProcessedOrderStatusID();
                break;

            case \Genesis\API\Constants\Transaction\Types::REFUND:
                $data['type'] = 'Refunded';
                $data['order_status_id'] = EComProcessingCheckoutSettings::getRefundedOrderStatusID();
                break;

            case \Genesis\API\Constants\Transaction\Types::VOID:
                $data['type'] = 'Voided';
                $data['order_status_id'] = EComProcessingCheckoutSettings::getCanceledOrderStatusID();
                break;
        }

        if (isset($data['type']) && isset($data['order_status_id'])) {
            EComProcessingCheckoutTransaction::setOrderStatus(
                $data['orders_id'],
                $data['order_status_id']
            );
            EComProcessingCheckoutTransaction::performOrderStatusHistory($data);
        }
    }

    /**
     * Used to determine the Module Transactions Table Name
     * @return string
     */
    protected function getTableNameTransactions()
    {
        return TABLE_ECOMPROCESSING_CHECKOUT_TRANSACTIONS;
    }

    /**
     * Get the sum of the ammount for a list of transaction types and status
     * @param int $order_id
     * @param string $reference_id
     * @param array $types
     * @param string $status
     * @return float
     */
    protected function getTransactionsSumAmount($order_id, $reference_id, $types, $status)
    {
        return EComProcessingCheckoutTransaction::getTransactionsSumAmount(
            $order_id,
            $reference_id,
            $types,
            $status
        );
    }

    /**
     * Get saved transaction by id
     *
     * @param string $reference_id UniqueId of the transaction
     *
     * @return mixed bool on fail, row on success
     */
    protected function getTransactionById($unique_id)
    {
        return EComProcessingCheckoutTransaction::getTransactionById($unique_id);
    }

    /**
     * Get the detailed transactions list of an order for transaction types and status
     * @param int $order_id
     * @param string $reference_id
     * @param array $transaction_types
     * @param string $status
     * @return array
     */
    protected function getTransactionsByTypeAndStatus($order_id, $reference_id, $transaction_types, $status)
    {
        return EComProcessingCheckoutTransaction::getTransactionsByTypeAndStatus(
            $order_id,
            $reference_id,
            $transaction_types,
            $status
        );
    }

    /**
     * Check to see whether module is installed
     *
     * @return boolean
     */
    public function check()
    {
        global $db;
        if (!isset($this->_check)) {
            $check_query =
                $db->Execute(
                    "select configuration_value from " . TABLE_CONFIGURATION . "
                     where configuration_key = '" .
                        EComProcessingCheckoutSettings::getCompleteSettingKey("STATUS") . "'"
                );
            $this->_check = $check_query->RecordCount();
        }
        return $this->_check;
    }

    /**
     * Registers Genesis autoload for a specific payment module.
     */
    protected function registerLibraries()
    {
        EComProcessingCheckoutTransactionProcess::bootstrap();
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->code = ECOMPROCESSING_CHECKOUT_CODE;
        $this->version = "1.0.2";
        parent::__construct();
    }

    protected function init()
    {
        $this->enabled = EComProcessingCheckoutSettings::getStatus();
        if (IS_ADMIN_FLAG === true) {
            // Payment module title in Admin
            $this->title = MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_TEXT_TITLE;

            if (EComProcessingCheckoutSettings::getIsInstalled()) {
                if (!EComProcessingCheckoutSettings::getIsConfigured()) {
                    $this->title .= '<span class="alert"> (Not Configured)</span>';
                } elseif (!EComProcessingCheckoutSettings::getStatus()) {
                    $this->title .= '<span class="alert"> (Disabled)</span>';
                } elseif (!EComProcessingCheckoutSettings::getIsLiveMode()) {
                    $this->title .= '<span class="alert-warning"> (Staging Mode)</span>';
                } else {
                    $this->title .= '<span class="alert-success"> (Live Mode)</span>';
                }
            }
        } else {
            // Payment module title in Catalog
            $this->title = MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_TEXT_PUBLIC_TITLE;
        }
        // Descriptive Info about module in Admin
        $this->description =
            sprintf(
                "<div style=\"text-align: center;\"><strong>%s</strong><br />(rev. %s)</div>",
                MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_TEXT_TITLE,
                $this->version
            ) .
            MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_TEXT_DESCRIPTION;
        // Sort Order of this payment option on the customer payment page
        $this->sort_order = EComProcessingCheckoutSettings::getSortOrder();
        $this->order_status = (int)DEFAULT_ORDERS_STATUS_ID;
        if (EComProcessingCheckoutSettings::getOrderStatusID() > 0) {
            $this->order_status = EComProcessingCheckoutSettings::getOrderStatusID();
        }

        parent::init();
    }

    /**
     * calculate zone matches and flag settings to determine whether this module should display to customers or not
     *
     */
    public function update_status()
    {
        $this->enabled = EComProcessingCheckoutSettings::getIsAvailableOnCheckoutPage();
    }

    /**
     * Display Information Submission Fields on the Checkout Payment Page
     *
     * @return array
     */
    public function selection()
    {
        $selection = array(
            'id' => $this->code,
            'module' =>
                EComProcessingCheckoutSettings::getCheckoutPageTitle(
                    MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_TEXT_TITLE
                ) .
                MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_TEXT_PUBLIC_CHECKOUT_CONTAINER
        );

        return $selection;

    }

    /**
     * Process a checkout request
     *
     * This method will try to create a new WPF instance
     * if successful - we redirect the customer on "after_process" to the newly created instance
     * if unsuccessful - we show them an error message and redirecting back to the CHECKOUT PAYMENT PAGE
     */
    public function before_process()
    {
        global $order, $messageStack;

        $data = new stdClass();
        $data->transaction_id = md5(uniqid() . microtime(true));
        $data->description = '';

        foreach ($order->products as $product) {
            $separator = ($product == end($order->products)) ? '' : PHP_EOL;

            $data->description .= $product['qty'] . ' x ' . $product['name'] . $separator;
        }

        $data->currency = $order->info['currency'];

        $data->language_id = EComProcessingCheckoutSettings::getLanguage();

        $data->urls = array(
            'notification'   =>
                EComProcessingCheckoutNotification::buildNotificationUrl(),
            'return_success' =>
                EComProcessingCheckoutNotification::buildReturnURL(
                    EComProcessingCheckoutNotification::ACTION_SUCCESS
                ),
            'return_failure' =>
                EComProcessingCheckoutNotification::buildReturnURL(
                    EComProcessingCheckoutNotification::ACTION_FAILURE
                ),
            'return_cancel' =>
                EComProcessingCheckoutNotification::buildReturnURL(
                    EComProcessingCheckoutNotification::ACTION_CANCEL
                )
        );

        $data->order = $order;

        $errorMessage = null;

        try {
            $this->responseObject = EComProcessingCheckoutTransactionProcess::pay($data);

            return true;
        } catch (\Genesis\Exceptions\ErrorAPI $api) {
            $errorMessage = $api->getMessage();
            $this->responseObject = null;
        } catch (\Genesis\Exceptions\ErrorNetwork $e) {
            $errorMessage = MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_MESSAGE_CHECK_CREDENTIALS .
                            PHP_EOL .
                            $e->getMessage();
            $this->responseObject = null;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->responseObject = null;
        }
        if (empty($this->responseObject) && !empty($errorMessage)) {
            $messageStack->add_session('checkout_payment', $errorMessage, 'error');
            zen_redirect(
                zen_href_link(
                    FILENAME_CHECKOUT_PAYMENT,
                    'payment_error=' . get_class($this),
                    'SSL'
                )
            );
        }
    }

    /**
     * Build admin-page components
     *
     * @param int $zf_order_id
     * @return string
     */
    public function admin_notification($zf_order_id)
    {
        if (EComProcessingCheckoutSettings::getIsInstalled()) {
            return parent::admin_notification($zf_order_id);
        } else {
            return false;
        }
    }

    /**
     * Install the payment module and its configuration settings
     *
     */
    public function install()
    {
        EComProcessingCheckoutInstaller::installModule();
    }

    /**
     * Remove the module and all its settings
     *
     */
    public function remove()
    {
        EComProcessingCheckoutInstaller::removeModule();
    }
    /**
     * Internal list of configuration keys used for configuration of the module
     *
     * @return array
     */
    public function keys()
    {
        return EComProcessingCheckoutSettings::getSettingKeys();
    }
}
