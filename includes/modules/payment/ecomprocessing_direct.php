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

use EComProcessing\Common                    as EComProcessingCommon;
use EComProcessing\Direct\Installer          as EComProcessingDirectInstaller;
use EComProcessing\Direct\Notification       as EComProcessingDirectNotification;
use EComProcessing\Direct\Settings           as EComProcessingDirectSettings;
use EComProcessing\Direct\Transaction        as EComProcessingDirectTransaction;
use EComProcessing\Direct\TransactionProcess as EComProcessingDirectTransactionProcess;
use EComProcessing\Direct\TemplateManager    as EComProcessingDirectTemplateManager;

class ecomprocessing_direct extends \EComProcessing\Base\PaymentMethod
{

    /**
     * this module collects card-info onsite
     */
    public $collectsCardDataOnsite = true;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->code = ECOMPROCESSING_DIRECT_CODE;
        $this->version = "1.0.4";
        parent::__construct();
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
                        EComProcessingDirectSettings::getCompleteSettingKey("STATUS") . "'"
                );
            $this->_check = $check_query->RecordCount();
        }
        return $this->_check;
    }

    /**
     * calculate zone matches and flag settings to determine whether this module should display to customers or not
     *
     */
    public function update_status()
    {
        $this->enabled = EComProcessingDirectSettings::getIsAvailableOnCheckoutPage();
    }

    /**
     * JS validation which does error-checking of data-entry if this module is selected for use
     * (Number, Owner, and CVV Lengths)
     *
     * @return string
     */
    public function javascript_validation()
    {
        //if (EComProcessingDirectSettings::getShouldUseIntegratedPaymentTemplate())
        //    return false;

        $js = '  if (payment_value == "' . $this->code . '") {' . "\n" .
              '    var cc_owner = jQuery("#' . $this->code . '_cc_owner").val();' . "\n" .
              '    var cc_number = jQuery("#' . $this->code . '_cc_number").val();' . "\n" .
              '    var cc_cvv = jQuery("#' . $this->code . '_cc_cvv").val();' . "\n" .
              '    var cc_expires_month = jQuery("#' . $this->code . '_cc_expires_month").val();' . "\n" .
              '    var cc_expires_year = jQuery("#' . $this->code . '_cc_expires_year").val();' . "\n" .
              '    if (cc_owner == "" || cc_owner.length < ' . CC_OWNER_MIN_LENGTH . ') {' . "\n" .
              '      error_message = error_message + "' . MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_JS_CC_OWNER . '";' . "\n" .
              '      error = 1;' . "\n" .
              '    }' . "\n" .
              '    if (cc_number == "" || cc_number.length < ' . CC_NUMBER_MIN_LENGTH . ') {' . "\n" .
              '      error_message = error_message + "' . MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_JS_CC_NUMBER . '";' . "\n" .
              '      error = 1;' . "\n" .
              '    }' . "\n" .
              '    if (cc_cvv == "" || cc_cvv.length < "3" || cc_cvv.length > "4") {' . "\n" .
              '      error_message = error_message + "' . MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_JS_CC_CVV . '";' . "\n" .
              '      error = 1;' . "\n" .
              '    }' . "\n" .
              '    if (cc_expires_month.length < 2 || cc_expires_year.length < 2) {' . "\n" .
              '      error_message = error_message + "' . MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_JS_CC_EXPIRES . '";' . "\n" .
              '      error = 1;' . "\n" .
              '    }' . "\n" .
              '  }' . "\n";

        return $js;
    }

    protected function getPaymentPageFields($order)
    {
        if (EComProcessingDirectSettings::getShouldUseIntegratedPaymentTemplate()) {
            return
                array(
                    array('field' =>
                        EComProcessingDirectTemplateManager::getCardHTMLContent(
                            array(
                                'title' =>
                                    MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_INTEGRATED_TPL_TITLE,
                                'card_controls' => array(
                                    "card_number" => array(
                                        'title'       =>
                                            MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_INTEGRATED_TPL_CARD_NUMBER,
                                        'placeholder' =>
                                            MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_INTEGRATED_TPL_CARD_NUMBER,
                                        'name'        =>
                                            $this->code . '_cc_number'
                                    ),
                                    "card_holder" => array(
                                        'title'       =>
                                            MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_INTEGRATED_TPL_CARD_OWNER,
                                        'placeholder' =>
                                            MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_INTEGRATED_TPL_CARD_OWNER,
                                        'name'        =>
                                            $this->code . '_cc_owner'
                                    ),
                                    "card_cvv" => array(
                                        'title'       =>
                                            MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_INTEGRATED_TPL_CARD_CVV,
                                        'placeholder' =>
                                            MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_INTEGRATED_TPL_CARD_CVV,
                                        'name'        =>
                                            $this->code . '_cc_cvv'
                                    ),
                                    "card_expiry" => array(
                                        'title'       =>
                                            MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_INTEGRATED_TPL_CARD_EXPIRY,
                                        'placeholder' =>
                                            MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_INTEGRATED_TPL_CARD_EXPIRY,
                                        'name'        =>
                                            $this->code . '_cc_expires'
                                    )
                                ),
                                'hidden' => array(
                                    'expiryMonth' =>
                                        $this->code . '_cc_expires_month',
                                    'expiryYear' =>
                                        $this->code . '_cc_expires_year'
                                )
                            )
                        )
                    )
                );
        } else {
            $onFocus = ' onfocus="methodSelect(\'pmt-' . $this->code . '\')"';

            for ($i = 1; $i < 13; $i++) {
                $expires_month[] = array(
                    'id' => sprintf('%02d', $i),
                    'text' =>
                        strftime(
                            '%B - (%m)',
                            mktime(0, 0, 0, $i, 1, 2000)
                        )
                );
            }

            $today = getdate();
            for ($i = $today['year']; $i < $today['year'] + 15; $i++) {
                $expires_year[] = array(
                    'id' =>
                        strftime(
                            '%y',
                            mktime(0, 0, 0, 1, 1, $i)
                        ),
                    'text' =>
                        strftime(
                            '%Y',
                            mktime(0, 0, 0, 1, 1, $i)
                        )
                );
            }

            return array(
                array(
                    'title' =>
                        MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_CREDIT_CARD_OWNER,
                    'field' =>
                        zen_draw_input_field(
                            $this->code . '_cc_owner',
                            $order->billing['firstname'] . ' ' . $order->billing['lastname'],
                            'id="' . $this->code . '-cc-owner"' . $onFocus . ' autocomplete="off"'
                        ),
                    'tag' => $this->code . '-cc-owner'
                ),
                array(
                    'title' =>
                        MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_CREDIT_CARD_NUMBER,
                    'field' =>
                        zen_draw_input_field(
                            $this->code . '_cc_number',
                            '',
                            'id="' . $this->code . '-cc-number"' . $onFocus . ' autocomplete="off"'
                        ),
                    'tag' =>
                        $this->code . '-cc-number'
                ),
                array(
                    'title' =>
                        MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_CREDIT_CARD_EXPIRES,
                    'field' =>
                        zen_draw_pull_down_menu(
                            $this->code . '_cc_expires_month',
                            $expires_month,
                            strftime('%m'),
                            'id="' . $this->code . '-cc-expires-month"' . $onFocus
                        ) .
                        '&nbsp;' .
                        zen_draw_pull_down_menu(
                            $this->code . '_cc_expires_year',
                            $expires_year,
                            '',
                            'id="' . $this->code . '-cc-expires-year"' . $onFocus
                        ),
                    'tag' =>
                        $this->code . '-cc-expires-month'
                ),
                array(
                    'title' =>
                        MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_CVV,
                    'field' =>
                        zen_draw_input_field(
                            $this->code . '_cc_cvv',
                            '',
                            'size="4" maxlength="4"' . ' id="' . $this->code . '-cc-cvv"' . $onFocus . ' autocomplete="off"'
                        ),
                    'tag' =>
                        $this->code . '-cc-cvv'
                )
            );
        }
    }
    /**
     * Display Information Submission Fields on the Checkout Payment Page
     *
     * @return array
     */
    public function selection()
    {
        global $order;

        $selection = array(
            'id' => $this->code,
            'module' =>
                EComProcessingDirectSettings::getCheckoutPageTitle(
                    MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_TITLE
                ) . MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_PUBLIC_CHECKOUT_CONTAINER,
            'fields' => $this->getPaymentPageFields($order)
        );

        return $selection;
    }

    /**
     * Evaluates the Credit Card Type for acceptance and the validity of the Credit Card Number & Expiration Date
     *
     */
    public function pre_confirmation_check()
    {
        global $messageStack;

        include(DIR_WS_CLASSES . 'cc_validation.php');

        $ccInfo = EComProcessingDirectTemplateManager::getPostedCCInfo($_POST);

        $cc_validation = new cc_validation();
        $result = $cc_validation->validate(
            $ccInfo['cc_number'],
            $ccInfo['cc_expires_month'],
            $ccInfo['cc_expires_year'],
            $ccInfo['cc_cvv']
        );

        $error = '';

        switch ($result) {
            case -1:
                $error = sprintf(
                    TEXT_CCVAL_ERROR_UNKNOWN_CARD,
                    substr($cc_validation->cc_number, 0, 4)
                );
                break;

            case -2:
            case -3:
            case -4:
                $error = TEXT_CCVAL_ERROR_INVALID_DATE;
                break;

            case false:
                $error = TEXT_CCVAL_ERROR_INVALID_NUMBER;
                break;
        }

        if (($result == false) || ($result < 1)) {
            $messageStack->add_session('checkout_payment', $error . '<!-- [' . $this->code . '] -->', 'error');
            zen_redirect(
                zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false)
            );
        }

        $this->cc_card_type = $cc_validation->cc_type;
        $this->cc_card_number = $cc_validation->cc_number;
        $this->cc_expiry_month = $cc_validation->cc_expiry_month;
        $this->cc_expiry_year = $cc_validation->cc_expiry_year;
    }

    /**
     * Display Credit Card Information on the Checkout Confirmation Page
     *
     * @return array
     */
    public function confirmation()
    {
        $confirmation = array(
            'fields' => array(
                array(
                    'title' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_CREDIT_CARD_TYPE,
                    'field' => $this->cc_card_type
                ),
                array(
                    'title' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_CREDIT_CARD_OWNER,
                    'field' => $_POST[$this->code . '_cc_owner']
                ),
                array(
                    'title' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_CREDIT_CARD_NUMBER,
                    'field' => substr($this->cc_card_number, 0, 4) .
                        str_repeat('X', (strlen($this->cc_card_number) - 8)) .
                        substr($this->cc_card_number, -4)
                ),
                array(
                    'title' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_CREDIT_CARD_EXPIRES,
                    'field' => strftime(
                        '%B, %Y',
                        mktime(
                            0,
                            0,
                            0,
                            $_POST[$this->code . '_cc_expires_month'],
                            1,
                            EComProcessingCommon::getCreditCardExpirationYear(
                                $_POST[$this->code . '_cc_expires_year']
                            )
                        )
                    )
                )
            )
        );

        return $confirmation;
    }

    public function process_button()
    {
        $process_button_string =
            zen_draw_hidden_field(
                'cc_owner',
                $_POST[$this->code . '_cc_owner']
            ) .
            zen_draw_hidden_field(
                'cc_expires',
                $this->cc_expiry_month . substr($this->cc_expiry_year, -2)
            ) .
            zen_draw_hidden_field(
                'cc_expiry_month',
                $this->cc_expiry_month
            ) .
            zen_draw_hidden_field(
                'cc_expiry_year',
                $this->cc_expiry_year
            ) .
            zen_draw_hidden_field(
                'cc_type',
                $this->cc_card_type
            ) .
            zen_draw_hidden_field(
                'cc_number',
                $this->cc_card_number
            ) .
            zen_draw_hidden_field(
                'cc_cvv',
                $_POST[$this->code . '_cc_cvv']
            ) .
            zen_draw_hidden_field(
                zen_session_name(),
                zen_session_id()
            );

        return $process_button_string;
    }

    /**
     * @return array
     */
    public function process_button_ajax()
    {
        $processButton = array(
            'ccFields' => array(
                'cc_number' => $this->code . '_cc_number',
                'cc_owner' => $this->code . '_cc_owner',
                'cc_cvv' => $this->code . '_cc_cvv',
                'cc_expires' => array(
                    'name' => 'concatExpiresFields',
                    'args' => "['{$this->code}_cc_expires_month','{$this->code}_cc_expires_year']"
                ),
                'cc_expiry_month' => $this->code . '_cc_expires_month',
                'cc_expiry_year' => $this->code . '_cc_expires_year',
                'cc_type' => $this->cc_card_type
            ),
            'extraFields' => array(
                zen_session_name() => zen_session_id()
            )
        );

        return $processButton;
    }

    /**
     * Process a direct request
     *
     * This method will try to create a new WPF instance
     * if successful - we redirect the customer on "payment success" page
     * if successful and is async transaction then we redirect to a custom 3D secure page to enter 3D secure code
     * if unsuccessful - we show them an error message and redirecting back to the CHECKOUT PAYMENT PAGE
     */
    public function before_process()
    {
        global $order, $messageStack;

        $data = new stdClass();
        $data->transaction_id = md5(uniqid() . microtime(true));
        $data->transaction_type = EComProcessingDirectSettings::getTransactionType();
        $data->description = '';

        $order->info['cc_type']    = $_POST['cc_type'];
        $order->info['cc_owner']   = $_POST['cc_owner'];
        $order->info['cc_number']  = $_POST['cc_number'];
        $order->info['cc_expires'] = $_POST['cc_expires'];
        $order->info['cc_cvv']     = '***';

        $data->card_info = array(
            'cc_owner'        => $_POST['cc_owner'],
            'cc_number'       => $_POST['cc_number'],
            'cc_expiry_month' => $_POST['cc_expiry_month'],
            'cc_expiry_year'  => EComProcessingCommon::getCreditCardExpirationYear(
                $_POST['cc_expiry_year']
            ),
            'cc_cvv'          => $_POST['cc_cvv']
        );

        foreach ($order->products as $product) {
            $separator = ($product == end($order->products)) ? '' : PHP_EOL;

            $data->description .= $product['qty'] . ' x ' . $product['name'] . $separator;
        }

        $data->currency = $order->info['currency'];

        if (EComProcessingDirectTransactionProcess::isAsyncTransaction($data->transaction_type)) {
            $data->urls = array(
                'notification' =>
                    EComProcessingDirectNotification::buildNotificationUrl(),
                'return_success' =>
                    EComProcessingDirectNotification::buildReturnURL(
                        EComProcessingDirectNotification::ACTION_SUCCESS
                    ),
                'return_failure' =>
                    EComProcessingDirectNotification::buildReturnURL(
                        EComProcessingDirectNotification::ACTION_FAILURE
                    )
            );
        }

        $data->order = $order;

        $errorMessage = null;

        try {
            $this->responseObject = EComProcessingDirectTransactionProcess::pay($data);
        } catch (\Genesis\Exceptions\ErrorAPI $api) {
            $errorMessage = $api->getMessage();
            $this->responseObject = null;
        } catch (\Genesis\Exceptions\ErrorNetwork $e) {
            $errorMessage = MODULE_PAYMENT_ECOMPROCESSING_DIRECT_MESSAGE_CHECK_CREDENTIALS .
                PHP_EOL .
                $e->getMessage();
            $this->responseObject = null;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->responseObject = null;
        }

        if (empty($this->responseObject)) {
            if (!empty($errorMessage)) {
                $messageStack->add_session('checkout_payment', $errorMessage, 'error');
            }

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
     * Updates Order Status and created Order Status History
     * from the Gateway Response
     * @param int $orderId
     * @return bool
     */
    protected function processUpdateOrder($orderId)
    {
        global $messageStack;

        if (!parent::processUpdateOrder($orderId)) {
            return false;
        }

        switch ($this->responseObject->status) {
            case \Genesis\API\Constants\Transaction\States::APPROVED:
                $orderStatusId = EComProcessingDirectSettings::getProcessedOrderStatusID();
                $isPaymentSuccessful = true;
                break;
            case \Genesis\API\Constants\Transaction\States::ERROR:
            case \Genesis\API\Constants\Transaction\States::DECLINED:
                $orderStatusId = EComProcessingDirectSettings::getFailedOrderStatusID();
                $isPaymentSuccessful = false;
                break;
            default:
                $orderStatusId = EComProcessingDirectSettings::getOrderStatusID();
                $isPaymentSuccessful = false;
        }

        EComProcessingDirectTransaction::setOrderStatus(
            $orderId,
            $orderStatusId
        );

        EComProcessingDirectTransaction::performOrderStatusHistory(
            array(
                'type'            => 'Gateway Response',
                'orders_id'       => $orderId,
                'order_status_id' => $orderStatusId,
                'payment'         => array(
                    'unique_id' =>
                        isset($this->responseObject->unique_id)
                            ? $this->responseObject->unique_id
                            : "",
                    'status'    =>
                        $this->responseObject->status,
                    'message'   =>
                        isset($this->responseObject->message)
                            ? $this->responseObject->message
                            : ""
                )
            )
        );

        if (!$isPaymentSuccessful) {
            $messageStack->add_session(
                'checkout_payment',
                MODULE_PAYMENT_ECOMPROCESSING_DIRECT_MESSAGE_PAYMENT_FAILED,
                'error'
            );
            zen_redirect(
                zen_href_link(
                    FILENAME_CHECKOUT_PAYMENT,
                    '',
                    'SSL'
                )
            );
        }

        return true;
    }

    /**
     * Build admin-page components
     *
     * @param int $zf_order_id
     * @return string
     */
    public function admin_notification($zf_order_id)
    {
        if (EComProcessingDirectSettings::getIsInstalled()) {
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
        EComProcessingDirectInstaller::installModule();
    }

    /**
     * Remove the module and all its settings
     *
     */
    public function remove()
    {
        EComProcessingDirectInstaller::removeModule();
    }

    /**
     * Internal list of configuration keys used for configuration of the module
     *
     * @return array
     */
    public function keys()
    {
        return EComProcessingDirectSettings::getSettingKeys();
    }

    /**
     * Generate Reference Transaction (Capture, Refund, Void)
     * @param string $transaction_type
     * @param stdClass $data
     * @return stdClass
     */
    protected function getReferenceTransactionResponse($transaction_type, $data)
    {
        return EComProcessingDirectTransactionProcess::$transaction_type($data);
    }

    /**
     * Extends the parameters needed for displaying the admin-page components
     * @param array $data
     */
    protected function extendOrderTransPanelData(&$data)
    {
        $data->params['modal'] = array(
            'capture' => array(
                'allowed' => EComProcessingDirectSettings::getIsPartialCaptureAllowed(),
                'form' => array(
                    'action' => 'doCapture',
                ),
                'input' => array(
                    'visible' => true,
                )
            ),
            'refund' => array(
                'allowed' => EComProcessingDirectSettings::getIsPartialRefundAllowed(),
                'form' => array(
                    'action' => 'doRefund',
                ),
                'input' => array(
                    'visible' => true,
                )
            ),
            'void' => array(
                'allowed' => EComProcessingDirectSettings::getIsVoidTransactionAllowed(),
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
                'title' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_TITLE,
                'transactions' => array(
                    'header' => array(
                        'id' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_ID,
                        'type' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_TYPE,
                        'timestamp' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_TIMESTAMP,
                        'amount' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_AMOUNT,
                        'status' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_STATUS,
                        'message' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_MESSAGE,
                        'mode' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_MODE,
                        'action_capture' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_ACTION_CAPTURE,
                        'action_refund' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_ACTION_REFUND,
                        'action_void' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_ACTION_VOID
                    )
                )
            ),
            'modal' => array(
                'capture' => array(
                    'title' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_CAPTURE_TRAN_TITLE,
                    'input' => array(
                        'label' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_MODAL_AMOUNT_LABEL_CAPTURE,
                        'warning_tooltip' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_MESSAGE_CAPTURE_PARTIAL_DENIED
                    ),
                    'buttons' => array(
                        'submit' => array(
                            'title' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_BUTTON_CAPTURE
                        ),
                        'cancel' => array(
                            'title' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_BUTTON_CANCEL
                        )
                    )
                ),
                'refund' => array(
                    'title' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_REFUND_TRAN_TITLE,
                    'input' => array(
                        'label' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_MODAL_AMOUNT_LABEL_REFUND,
                        'warning_tooltip' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_MESSAGE_REFUND_PARTIAL_DENIED
                    ),
                    'buttons' => array(
                        'submit' => array(
                            'title' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_BUTTON_REFUND
                        ),
                        'cancel' => array(
                            'title' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_BUTTON_CANCEL
                        )
                    )
                ),
                'void' => array(
                    'title' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_VOID_TRAN_TITLE,
                    'input' => array(
                        'label' => null,
                        'warning_tooltip' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_MESSAGE_VOID_DENIED
                    ),
                    'buttons' => array(
                        'submit' => array(
                            'title' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_BUTTON_VOID
                        ),
                        'cancel' => array(
                            'title' => MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_BUTTON_CANCEL
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
        EComProcessingDirectTransaction::populateTransaction($data);
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
                $data['order_status_id'] = EComProcessingDirectSettings::getProcessedOrderStatusID();
                break;

            case \Genesis\API\Constants\Transaction\Types::REFUND:
                $data['type'] = 'Refunded';
                $data['order_status_id'] = EComProcessingDirectSettings::getRefundedOrderStatusID();
                break;

            case \Genesis\API\Constants\Transaction\Types::VOID:
                $data['type'] = 'Voided';
                $data['order_status_id'] = EComProcessingDirectSettings::getCanceledOrderStatusID();
                break;
        }

        if (isset($data['type']) && isset($data['order_status_id'])) {
            EComProcessingDirectTransaction::setOrderStatus(
                $data['orders_id'],
                $data['order_status_id']
            );
            EComProcessingDirectTransaction::performOrderStatusHistory($data);
        }
    }

    /**
     * Used to determine the Module Transactions Table Name
     * @return string
     */
    protected function getTableNameTransactions()
    {
        return TABLE_ECOMPROCESSING_DIRECT_TRANSACTIONS;
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
        return EComProcessingDirectTransaction::getTransactionsSumAmount(
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
        return EComProcessingDirectTransaction::getTransactionById($unique_id);
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
        return EComProcessingDirectTransaction::getTransactionsByTypeAndStatus(
            $order_id,
            $reference_id,
            $transaction_types,
            $status
        );
    }

    /**
     * Registers Genesis autoload for a specific payment module.
     */
    protected function registerLibraries()
    {
        EComProcessingDirectTransactionProcess::bootstrap();
    }

    protected function init()
    {
        $this->enabled = EComProcessingDirectSettings::getStatus();
        if (IS_ADMIN_FLAG === true) {
            // Payment module title in Admin
            $this->title = MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_TITLE;

            if (EComProcessingDirectSettings::getIsInstalled()) {
                if (!EComProcessingDirectSettings::getIsConfigured()) {
                    $this->title .= '<span class="alert"> (Not Configured)</span>';
                } elseif (!EComProcessingDirectSettings::getStatus()) {
                    $this->title .= '<span class="alert"> (Disabled)</span>';
                } elseif (!EComProcessingCommon::getIsSSLEnabled()) {
                    $this->title .= '<span class="alert"> (SSL NOT Enabled)</span>';
                } elseif (!EComProcessingDirectSettings::getIsLiveMode()) {
                    $this->title .= '<span class="alert-warning"> (Staging Mode)</span>';
                } else {
                    $this->title .= '<span class="alert-success"> (Live Mode)</span>';
                }
            }
        } else {
            // Payment module title in Catalog
            $this->title = MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_PUBLIC_TITLE;
        }
        // Descriptive Info about module in Admin
        $this->description =
            sprintf(
                "<div style=\"text-align: center;\"><strong>%s</strong><br />(rev. %s)</div>",
                MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_TITLE,
                $this->version
            ) .
            MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_DESCRIPTION;
        // Sort Order of this payment option on the customer payment page
        $this->sort_order = EComProcessingDirectSettings::getSortOrder();
        $this->order_status = (int)DEFAULT_ORDERS_STATUS_ID;
        if (EComProcessingDirectSettings::getOrderStatusID() > 0) {
            $this->order_status = EComProcessingDirectSettings::getOrderStatusID();
        }

        parent::init();
    }
}
