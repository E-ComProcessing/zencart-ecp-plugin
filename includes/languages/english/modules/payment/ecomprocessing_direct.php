<?php
/**
 * E-Comprocessing Direct English Language file
 *
 * Contains English translation for strings used in the
 * E-Comprocessing Checkout module
 *
 * @license     http://opensource.org/licenses/MIT The MIT License
 * @copyright   2018 E-Comprocessing Ltd.
 * @version     $Id:$
 * @since       1.0.0
 */

define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_TITLE', 'E-Comprocessing Direct');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_PUBLIC_CHECKOUT_CONTAINER', '<img style="border: 0px none; margin-left: 50pt; display: block" src="images/ecomprocessing/logos/ecp_direct.png" /> <br> <span style="display: block; font-weight: bold; margin-left: 50pt;">E-Comprocessing offers a secure way to pay for your order, using Credit/Debit/Prepaid Card</span>');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_PUBLIC_TITLE', 'E-Comprocessing Direct <img style="border: 0px none; display: block" src="images/ecomprocessing/logos/ecp_direct.png" />');

define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_DESCRIPTION', '<a href="https://e-comprocessing.com" target="_blank" style="width: 50%; display: block; margin: 0px auto;"><img style="border: 0px none; margin: 0px; width: 100%;" src="images/ecomprocessing/logos/ecomprocessing.png"/></a> <br> Direct API - allow customers to enter their CreditCard information on your website. Note: You need PCI-DSS certificate in order to enable this payment method. <br/> <br/> <img style="border: 0px none; margin: 0 auto; display: block" src="images/ecomprocessing/logos/ecp_direct.png" /> <br/> <a href="https://e-comprocessing.com" target="_blank" style="text-decoration:underline;font-weight:bold; display: block; text-align: center;">Visit E-Comprocessing\'s Website</a> ');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_CATALOG_TITLE', 'Credit Card');  // Payment option title as displayed to the customer
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_CREDIT_CARD_TYPE', 'Card Type:');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_CREDIT_CARD_OWNER', 'Card Owner:');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_CREDIT_CARD_NUMBER', 'Card Number:');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_CREDIT_CARD_EXPIRES', 'Expiry Date:');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_CVV', 'CVV Number:');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_INTEGRATED_TPL_TITLE', 'Pay with Credit / Debit Card');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_INTEGRATED_TPL_CARD_OWNER', 'Card holder');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_INTEGRATED_TPL_CARD_NUMBER', 'Card number');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_INTEGRATED_TPL_CARD_CVV', 'CVV / CVV2 / CSC');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_INTEGRATED_TPL_CARD_EXPIRY', 'Expiration date (month / year)');

define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_POPUP_CVV_LINK', 'What\'s this?');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_JS_CC_OWNER', '* The owner\'s name of the credit card must be at least ' . CC_OWNER_MIN_LENGTH . ' characters.\n');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_JS_CC_NUMBER', '* The credit card number must be at least ' . CC_NUMBER_MIN_LENGTH . ' characters.\n');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_JS_CC_CVV', '* The 3 or 4 digit CVV number must be entered from the back of the credit card.\n');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_JS_CC_EXPIRES', '*  The expiration date entered for the credit card is invalid. Please check the date and try again. (Ex. mm / yy). \n');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_DECLINED_MESSAGE', 'Your credit card could not be authorized for this reason. Please correct the information and try again or contact us for further assistance.');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_ERROR', 'Credit Card Error!');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_AUTHENTICITY_WARNING', 'WARNING: Security hash problem. Please contact store-owner immediately. Your order has *not* been fully authorized.');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_TEXT_COMM_ERROR', 'Unable to process payment due to a communications error. You may try again or contact us for assistance.');


//messages
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_MESSAGE_PAYMENT_SUCCESSFUL', 'Payment successful');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_MESSAGE_PAYMENT_CANCELED', 'You have successfully cancelled your order.');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_MESSAGE_PAYMENT_FAILED', 'Please, check your input and try again.');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_MESSAGE_CAPTURE_PARTIAL_DENIED', 'Partial Capture is currently disabled!');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_MESSAGE_REFUND_PARTIAL_DENIED', 'Partial Refund is currently disabled!');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_MESSAGE_VOID_DENIED', 'Cancel Transaction are currently disabled. You can enable this option in the Module Settings.');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_MESSAGE_ENTER_ALL_REQUIRED_DATA', 'Please, make sure you\'ve entered all of the required data correctly, e.g. Email, Phone, Billing/Shipping Address.');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_MESSAGE_CHECK_CREDENTIALS', 'Please, make sure you\'ve properly entered your module credentials.');

//entries
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_TITLE', 'E-Comprocessing Transactions');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_CAPTURE_TRAN_TITLE', 'Capture Transaction');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_REFUND_TRAN_TITLE', 'Refund Transaction');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_VOID_TRAN_TITLE', 'Void Transaction');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_BUTTON_CANCEL', 'Close');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_BUTTON_CAPTURE', 'Capture');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_BUTTON_REFUND', 'Refund');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_BUTTON_VOID', 'Void');

define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_ID', 'Id');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_TYPE', 'Type');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_TIMESTAMP', 'Date');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_AMOUNT', 'Amount');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_STATUS', 'Status');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_MESSAGE', 'Message');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_MODE', 'Mode');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_ACTION_CAPTURE', 'Capture');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_ACTION_REFUND', 'Refund');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_HEADER_ACTION_VOID', 'Void');

define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_MODAL_AMOUNT_LABEL_CAPTURE', 'Capture amount');
define('MODULE_PAYMENT_ECOMPROCESSING_DIRECT_LABEL_ORDER_TRANS_MODAL_AMOUNT_LABEL_REFUND', 'Refund amount');



