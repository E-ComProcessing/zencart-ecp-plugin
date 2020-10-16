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

abstract class Installer
{
    /**
     * Transaction DatabaseTableName
     * @var string
     */
    static protected $table_name = null;

    /**
     * Settings Values Prefix
     * @var string
     */
    static protected $settings_prefix = null;

    /**
     * Checks if class overridden properly
     * @throws \Exception
     */
    private static function check()
    {
        if (empty(static::$table_name)) {
            throw new \Exception("TablePrefix not set");
        }

        if (empty(static::$settings_prefix)) {
            throw new \Exception("SettingsPrefix not set");
        }
    }

    /**
     * Do on module install
     * @throws \Exception
     */
    public static function installModule()
    {
        global $db;
        static::check();

        $db->Execute('CREATE TABLE IF NOT EXISTS `' . static::$table_name . '` (
                          `unique_id` varchar(255) NOT NULL,
                          `reference_id` varchar(255) NOT NULL,
                          `order_id` int(11) NOT NULL,
                          `type` char(32) NOT NULL,
                          `mode` char(255) NOT NULL,
                          `timestamp` datetime NOT NULL,
                          `status` char(32) NOT NULL,
                          `message` varchar(255) DEFAULT NULL,
                          `technical_message` varchar(255) DEFAULT NULL,
                          `terminal_token` varchar(255) DEFAULT NULL,
                          `amount` decimal(10,2) DEFAULT NULL,
                          `currency` char(3) DEFAULT NULL
                        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ');
    }

    /**
     * Do on module remove
     * @throws \Exception
     */
    public static function removeModule()
    {
        global $db;
        static::check();

        $db->Execute("delete from " . TABLE_CONFIGURATION .
                     " where configuration_key like '" . static::$settings_prefix . "%'");
        $db->Execute('DROP TABLE IF EXISTS `' . static::$table_name . '`');
    }
}
