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

namespace EComProcessing\Direct;

use \EComProcessing\Direct\Settings as EComProcessingDirectSettings;
use \EComProcessing\Common          as EComProcessingCommon;

class Installer extends \EComProcessing\Base\Installer
{
    /**
     * Transaction DatabaseTableName
     * @var string
     */
    static protected $table_name = TABLE_ECOMPROCESSING_DIRECT_TRANSACTIONS;

    /**
     * Settings Values Prefix
     * @var string
     */
    static protected $settings_prefix = ECOMPROCESSING_DIRECT_SETTINGS_PREFIX;

    /**
     * Do on module install
     * @throws \Exception
     */
    public static function installModule()
    {
        global $db, $messageStack;

        if (EComProcessingDirectSettings::getIsInstalled()) {
            $messageStack->add_session('E-Comprocessing Direct module already installed.', 'error');
            zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=' . ECOMPROCESSING_DIRECT_CODE, 'NONSSL'));
            return 'failed';
        }

        parent::installModule();

        $transaction_types = EComProcessingCommon::buildSettingsDropDownOptions(
            EComProcessingDirectSettings::getTransactionsList()
        );

        $payment_templates = EComProcessingCommon::buildSettingsDropDownOptions(
            EComProcessingDirectSettings::getPaymentTemplatesList()
        );

        $sortOrderAttributes = "array(''maxlength'' => ''3'')";
        $requiredOptionsAttributes = "array(''required'' => ''required'')";

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Enable E-Comprocessing Direct Module', '" . EComProcessingDirectSettings::getCompleteSettingKey('STATUS') . "', 'true', 'Do you want to process payments via E-Comprocessing''s Genesis Gateway?', '6', '3', 'ecp_zfg_draw_toggle(', 'ecp_zfg_get_toggle_value', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Checkout Title', '" . EComProcessingDirectSettings::getCompleteSettingKey('CHECKOUT_PAGE_TITLE') . "', 'Pay safely with E-Comprocessing Direct', 'This name will be displayed on the checkout page', '6', '4', 'ecp_zfg_draw_input(null, ', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Genesis API Username', '" . EComProcessingDirectSettings::getCompleteSettingKey('USERNAME') . "', '', 'Enter your Username, required for accessing the Genesis Gateway', '6', '4', 'ecp_zfg_draw_input({$requiredOptionsAttributes}, ', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Genesis API Password', '" . EComProcessingDirectSettings::getCompleteSettingKey('PASSWORD') . "', '', 'Enter your Password, required for accessing the Genesis Gateway', '6', '4', 'ecp_zfg_draw_input({$requiredOptionsAttributes}, ', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Genesis API Token', '" . EComProcessingDirectSettings::getCompleteSettingKey('TOKEN') . "', '', 'Enter your Token, required for accessing the Genesis Gateway', '6', '4', 'ecp_zfg_draw_input({$requiredOptionsAttributes}, ', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Live Mode', '" . EComProcessingDirectSettings::getCompleteSettingKey('ENVIRONMENT') . "', 'false', 'If disabled, transactions are going through our Staging (Test) server, NO MONEY ARE BEING TRANSFERRED', '6', '3', 'ecp_zfg_draw_toggle(', 'ecp_zfg_get_toggle_value', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Transaction Type', '" . EComProcessingDirectSettings::getCompleteSettingKey('TRANSACTION_TYPE') . "', '" . \Genesis\API\Constants\Transaction\Types::SALE . "', 'What transaction type should we use upon purchase?.', '6', '0', 'ecp_zfg_select_drop_down_single({$transaction_types}, ', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Partial Capture', '" . EComProcessingDirectSettings::getCompleteSettingKey('ALLOW_PARTIAL_CAPTURE') . "', 'true', 'Use this option to allow / deny Partial Capture Transactions', '6', '3', 'ecp_zfg_draw_toggle(', 'ecp_zfg_get_toggle_value', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Partial Refund', '" . EComProcessingDirectSettings::getCompleteSettingKey('ALLOW_PARTIAL_REFUND') . "', 'true', 'Use this option to allow / deny Partial Refund Transactions', '6', '3', 'ecp_zfg_draw_toggle(', 'ecp_zfg_get_toggle_value', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Cancel Transaction', '" . EComProcessingDirectSettings::getCompleteSettingKey('ALLOW_VOID_TRANSACTIONS') . "', 'true', 'Use this option to allow / deny Cancel Transactions', '6', '3', 'ecp_zfg_draw_toggle(', 'ecp_zfg_get_toggle_value', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Sort order of display.', '" . EComProcessingDirectSettings::getCompleteSettingKey('SORT_ORDER') . "', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', 'ecp_zfg_draw_number_input({$sortOrderAttributes}, ', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Payment Template', '" . EComProcessingDirectSettings::getCompleteSettingKey('PAYMENT_TEMPLATE') . "', 'integrated', 'Choose the template for the payment page. If you choose the default ZenCart-Template, the default ZenCart template will be used, otherwise the integrated E-Comprocessing template', '6', '3', 'ecp_zfg_select_drop_down_single({$payment_templates}, ', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Default Order Status', '" . EComProcessingDirectSettings::getCompleteSettingKey('ORDER_STATUS_ID') . "', '1', 'Set the default status of orders made with this payment module to this value', '6', '0', 'ecp_zfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Failed Order Status', '" . EComProcessingDirectSettings::getCompleteSettingKey('FAILED_ORDER_STATUS_ID') . "', '1', 'Set the status of failed orders made with this payment module to this value', '6', '0', 'ecp_zfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Processed Order Status', '" . EComProcessingDirectSettings::getCompleteSettingKey('PROCESSED_ORDER_STATUS_ID') . "', '2', 'Set the status of processed orders made with this payment module to this value', '6', '0', 'ecp_zfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Refunded Order Status', '" . EComProcessingDirectSettings::getCompleteSettingKey('REFUNDED_ORDER_STATUS_ID') . "', '1', 'Set the status of refunded orders made with this payment module', '6', '0', 'ecp_zfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Canceled Order Status', '" . EComProcessingDirectSettings::getCompleteSettingKey('CANCELED_ORDER_STATUS_ID') . "', '1', 'Set the status of canceled orders made with this payment module', '6', '0', 'ecp_zfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    }
}
