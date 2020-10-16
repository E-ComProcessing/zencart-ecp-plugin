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

namespace EComProcessing\Base;

abstract class Notification
{
    const ACTION_SUCCESS = 'success';
    const ACTION_CANCEL = 'cancel';
    const ACTION_FAILURE = 'failure';
    const ACTION_NOTIFY = 'notify';

    /**
     * ModuleCode, used for redirections and loading files
     * @var string
     */
    protected static $module_code = null;

    /**
     * Build Return URL from Genesis
     * @param string $action
     * @return string
     */
    public static function buildReturnURL($action)
    {
        return null;
    }

    /**
     * Resets ZenCart Checkout Sessions (On Successful Payment)
     */
    public static function resetCartSessions()
    {
        $_SESSION['cart']->reset(true);
        unset($_SESSION['sendto']);
        unset($_SESSION['billto']);
        unset($_SESSION['shipping']);
        unset($_SESSION['payment']);
        unset($_SESSION['comments']);
    }

    /**
     * Process Genesis Notification
     * @param array $requestData
     */
    protected static function processNotification($requestData)
    {
    }

    /**
     * Process Return Action
     * @param string $action
     * @return void
     */
    protected static function processReturnAction($action)
    {
        global $messageStack;

        switch ($action) {
            case static::ACTION_SUCCESS:
                static::resetCartSessions();
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
                break;

            case static::ACTION_FAILURE:
                $messageStack->add_session(
                    'checkout_payment',
                    constant("MODULE_PAYMENT_" . strtoupper(static::$module_code) . "_MESSAGE_PAYMENT_FAILED"),
                    'error'
                );
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
                break;

            case static::ACTION_CANCEL:
                $messageStack->add_session(
                    'checkout_payment',
                    constant("MODULE_PAYMENT_" . strtoupper(static::$module_code) . "_MESSAGE_PAYMENT_CANCELED"),
                    'caution'
                );
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
                break;
        }
    }

    /**
     * Build NotificationURL for Genesis
     * @return string
     */
    public static function buildNotificationUrl()
    {
        return static::buildReturnURL(
            static::ACTION_NOTIFY
        );
    }

    /**
     * Handle Genesis Notification / Redirects
     * @param array $requestData
     */
    public static function handleNotification($requestData)
    {
        if (!isset($_GET['return'])) {
            return;
        }

        if (!defined('DIR_FS_CATALOG_LANGUAGES')) {
            define('DIR_FS_CATALOG_LANGUAGES', DIR_FS_CATALOG . 'includes/languages/');
        }

        $moduleLanguageFile =
            DIR_FS_CATALOG_LANGUAGES .
            $_SESSION['language'] .
            '/modules/payment/' .
            static::$module_code . '.php';

        if (file_exists($moduleLanguageFile)) {
            require_once($moduleLanguageFile);
        }

        $action = $_GET['return'];

        if ($action == static::ACTION_NOTIFY) {
            static::processNotification($requestData);
        } else {
            static::processReturnAction($action);
        }
    }


}
