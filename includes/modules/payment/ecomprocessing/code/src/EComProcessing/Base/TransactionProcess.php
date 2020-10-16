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

class TransactionProcess
{

    /**
     * Set Genesis Config Values (Ex. Login, Password, Token, etc)
     */
    protected static function doLoadGenesisPrivateConfigValues()
    {
        \Genesis\Config::setEndpoint(
            \Genesis\API\Constants\Endpoints::ECOMPROCESSING
        );
    }

    /**
     * Generate Transaction Id based on the order id
     * and salted to avoid duplication
     *
     * @param string $prefix
     *
     * @return string
     */
    public static function genTransactionId($prefix = '')
    {
        $hash = md5(microtime(true) . uniqid() . mt_rand(PHP_INT_SIZE, PHP_INT_MAX));

        return (string)$prefix . substr($hash, -(strlen($hash) - strlen($prefix)));
    }

    /**
     * Bootstrap Genesis Library
     *
     * @param string $token Terminal token
     *
     * @return void
     */
    public static function bootstrap()
    {
        if (!class_exists('\Genesis\Genesis', false)) {
            include DIR_FS_CATALOG . DIR_WS_INCLUDES . 'modules/payment/ecomprocessing/libs/genesis/vendor/autoload.php';
        }

        static::doLoadGenesisPrivateConfigValues();
    }

    /**
     * Send transaction to Genesis
     *
     * @param $data array Transaction Data
     * @return \stdClass
     * @throws \Exception
     * @throws \Genesis\Exceptions\ErrorAPI
     */
    public static function pay($data)
    {
        return null;
    }

    /**
     * Execute Genesis Reference Transaction (Capture, Refund, Void)
     * @param string $transaction_type
     * @param \stdClass $data
     * @return \stdClass
     * @throws \Exception
     */
    private static function executeReferenceTransaction($transaction_type, $data)
    {
        try {
            static::bootstrap();

            static::setTerminalToken($data->reference_id);

            $genesis = new \Genesis\Genesis('Financial\\' . ucfirst($transaction_type));

            $genesis
                ->request()
                    ->setTransactionId(
                        static::genTransactionId('zencart-')
                    )
                    ->setRemoteIp($data->remote_address)
                    ->setUsage($data->usage)
                    ->setReferenceId($data->reference_id);

            if ($transaction_type != \Genesis\API\Constants\Transaction\Types::VOID) {
                $genesis
                    ->request()
                        ->setAmount($data->amount)
                        ->setCurrency($data->currency);
            }

            $genesis->execute();

            return $genesis->response()->getResponseObject();
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Send Capture transaction to the Gateway
     *
     * @param string $reference_id ReferenceId
     * @param string $amount Amount to be refunded
     * @param string $currency Currency for the refunded amount
     * @param string $usage Usage (optional text)
     * @param string $token Terminal token of the initial transaction
     *
     * @return object
     */

    public static function capture($data)
    {
        return static::executeReferenceTransaction(
            \Genesis\API\Constants\Transaction\Types::CAPTURE,
            $data
        );
    }

    /**
     * Send Refund transaction to the Gateway
     *
     * @param string $reference_id ReferenceId
     * @param string $amount Amount to be refunded
     * @param string $currency Currency for the refunded amount
     * @param string $usage Usage (optional text)
     * @param string $token Terminal token of the initial transaction
     *
     * @return object
     */
    public static function refund($data)
    {
        return static::executeReferenceTransaction(
            \Genesis\API\Constants\Transaction\Types::REFUND,
            $data
        );
    }

    /**
     * Send Void transaction to the Gateway
     *
     * @param string $reference_id ReferenceId
     * @param string $usage Usage (optional text)
     * @param string $token Terminal token of the initial transaction
     *
     * @return object
     */
    public static function void($data)
    {
        return self::executeReferenceTransaction(
            \Genesis\API\Constants\Transaction\Types::VOID,
            $data
        );
    }

    /**
     * Set Genesis Terminal Token
     * @param string $reference_id
     */
    public static function setTerminalToken($reference_id)
    {
    }

    /**
     * Gets state code (zone code) if available,
     * otherwise gets state name (zone name)
     *
     * @param array $address
     *
     * @return string
     */
    protected static function getStateCode($address)
    {
        $state = $address['state'];

        if (true && isset($address['country_id']) && zen_not_null($address['country_id'])) {
            if (isset($address['zone_id']) && zen_not_null($address['zone_id'])) {
            $state = zen_get_zone_code($address['country_id'], $address['zone_id'], $state);
            }
        }

        return $state;
    }
}
