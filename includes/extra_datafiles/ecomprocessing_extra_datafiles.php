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

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

define('ECOMPROCESSING_CHECKOUT_CODE', 'ecomprocessing_checkout');
define('FILENAME_EMECHANTPAY_CHECKOUT_IPN', 'ecomprocessing_checkout_ipn');
define('ECOMPROCESSING_CHECKOUT_SETTINGS_PREFIX', 'MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_');
define('TABLE_ECOMPROCESSING_CHECKOUT_TRANSACTIONS', DB_PREFIX . 'ecomprocessing_checkout_transactions');

define('ECOMPROCESSING_DIRECT_CODE', 'ecomprocessing_direct');
define('FILENAME_EMECHANTPAY_DIRECT_IPN', 'ecomprocessing_direct_ipn');
define('ECOMPROCESSING_DIRECT_SETTINGS_PREFIX', 'MODULE_PAYMENT_ECOMPROCESSING_DIRECT_');
define('TABLE_ECOMPROCESSING_DIRECT_TRANSACTIONS', DB_PREFIX . 'ecomprocessing_direct_transactions');
