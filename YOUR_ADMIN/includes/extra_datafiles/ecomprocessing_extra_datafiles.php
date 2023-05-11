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

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

define('ECOMPROCESSING_CHECKOUT_CODE', 'ecomprocessing_checkout');
define('FILENAME_ECOMPROCESSING_CHECKOUT_IPN', 'ecomprocessing_checkout_ipn');
define(
    'ECOMPROCESSING_CHECKOUT_SETTINGS_PREFIX',
    'MODULE_PAYMENT_ECOMPROCESSING_CHECKOUT_'
);
define(
    'TABLE_ECOMPROCESSING_CHECKOUT_TRANSACTIONS',
    DB_PREFIX . 'ecomprocessing_checkout_transactions'
);
define(
    'TABLE_ECOMPROCESSING_CHECKOUT_CONSUMERS',
    DB_PREFIX . 'ecomprocessing_checkout_consumers'
);

define('PPRO_TRANSACTION_SUFFIX', '_ppro');
define('GOOGLE_PAY_TRANSACTION_PREFIX', 'google_pay_');
define('GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE', 'authorize');
define('GOOGLE_PAY_PAYMENT_TYPE_SALE', 'sale');
define('PAYPAL_TRANSACTION_PREFIX', 'pay_pal_');
define('PAYPAL_PAYMENT_TYPE_AUTHORIZE', 'authorize');
define('PAYPAL_PAYMENT_TYPE_SALE', 'sale');
define('PAYPAL_PAYMENT_TYPE_EXPRESS', 'express');
define('APPLE_PAY_TRANSACTION_PREFIX', 'apple_pay_');
define('APPLE_PAY_PAYMENT_TYPE_AUTHORIZE', 'authorize');
define('APPLE_PAY_PAYMENT_TYPE_SALE', 'sale');
define('METHOD_ACTION_CAPTURE', 'capture');
define('METHOD_ACTION_REFUND', 'refund');
