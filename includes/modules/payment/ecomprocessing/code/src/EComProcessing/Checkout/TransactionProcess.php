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

namespace EComProcessing\Checkout;

use \EComProcessing\Checkout\Settings as EComProcessingCheckoutSettings;
use \EComProcessing\Checkout\Transaction as EComProcessingCheckoutTransaction;

class TransactionProcess extends \EComProcessing\Base\TransactionProcess
{

    /**
     * Set Genesis Config Values (Ex. Login, Password, Token, etc)
     */
    protected static function doLoadGenesisPrivateConfigValues()
    {
        parent::doLoadGenesisPrivateConfigValues();

        if (EComProcessingCheckoutSettings::getIsConfigured()) {
            \Genesis\Config::setUsername(
                EComProcessingCheckoutSettings::getUserName()
            );

            \Genesis\Config::setPassword(
                EComProcessingCheckoutSettings::getPassword()
            );

            \Genesis\Config::setEnvironment(
                EComProcessingCheckoutSettings::getIsLiveMode()
                    ? \Genesis\API\Constants\Environments::PRODUCTION
                    : \Genesis\API\Constants\Environments::STAGING
            );
        }
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
        try {
            $genesis = new \Genesis\Genesis('WPF\Create');

            $genesis
                ->request()
                    ->setTransactionId($data->transaction_id)
                    ->setUsage('ZenCart Electronic Transaction')
                    ->setDescription($data->description)
                    ->setNotificationUrl($data->urls['notification'])
                    ->setReturnSuccessUrl($data->urls['return_success'])
                    ->setReturnFailureUrl($data->urls['return_failure'])
                    ->setReturnCancelUrl($data->urls['return_cancel'])
                    ->setCurrency($data->currency)
                    ->setAmount($data->order->info['total'])
                    ->setCustomerEmail($data->order->customer['email_address'])
                    ->setCustomerPhone($data->order->customer['telephone'])
                    ->setBillingFirstName($data->order->billing['firstname'])
                    ->setBillingLastName($data->order->billing['lastname'])
                    ->setBillingAddress1($data->order->billing['street_address'])
                    ->setBillingZipCode($data->order->billing['postcode'])
                    ->setBillingCity($data->order->billing['city'])
                    ->setBillingState( self::getStateCode($data->order->billing) )
                    ->setBillingCountry($data->order->billing['country']['iso_code_2'])
                    ->setShippingFirstName($data->order->delivery['firstname'])
                    ->setShippingLastName($data->order->delivery['lastname'])
                    ->setShippingAddress1($data->order->delivery['street_address'])
                    ->setShippingZipCode($data->order->delivery['postcode'])
                    ->setShippingCity($data->order->delivery['city'])
                    ->setShippingState( self::getStateCode($data->order->delivery) )
                    ->setShippingCountry($data->order->delivery['country']['iso_code_2'])
                    ->setLanguage($data->language_id);

            foreach (static::getCheckoutTransactionTypes() as $type) {
                if (is_array($type)) {
                    $genesis
                        ->request()
                            ->addTransactionType($type['name'], $type['parameters']);
                } else {
                    $genesis
                        ->request()
                            ->addTransactionType($type);
                }
            }

            $genesis->execute();

            return $genesis->response()->getResponseObject();
        } catch (\Genesis\Exceptions\ErrorAPI $api) {
            throw $api;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Get Available Checkout Transaction Types
     * @return array
     */
    private static function getCheckoutTransactionTypes()
    {
        $processed_list = array();

        $selected_types = EComProcessingCheckoutSettings::getTransactionTypes();

        $alias_map = array(
            \Genesis\API\Constants\Payment\Methods::EPS         =>
                \Genesis\API\Constants\Transaction\Types::PPRO,
            \Genesis\API\Constants\Payment\Methods::GIRO_PAY    =>
                \Genesis\API\Constants\Transaction\Types::PPRO,
            \Genesis\API\Constants\Payment\Methods::PRZELEWY24  =>
                \Genesis\API\Constants\Transaction\Types::PPRO,
            \Genesis\API\Constants\Payment\Methods::QIWI        =>
                \Genesis\API\Constants\Transaction\Types::PPRO,
            \Genesis\API\Constants\Payment\Methods::SAFETY_PAY  =>
                \Genesis\API\Constants\Transaction\Types::PPRO,
            \Genesis\API\Constants\Payment\Methods::TELEINGRESO =>
                \Genesis\API\Constants\Transaction\Types::PPRO,
            \Genesis\API\Constants\Payment\Methods::TRUST_PAY   =>
                \Genesis\API\Constants\Transaction\Types::PPRO,
        );

        foreach ($selected_types as $selected_type) {
            if (array_key_exists($selected_type, $alias_map)) {
                $transaction_type = $alias_map[$selected_type];

                $processed_list[$transaction_type]['name'] = $transaction_type;

                $processed_list[$transaction_type]['parameters'][] = array(
                    'payment_method' => $selected_type
                );
            } else {
                $processed_list[] = $selected_type;
            }
        }

        return $processed_list;
    }

    /**
     * Set Genesis Terminal Token
     * @param string $reference_id
     */
    public static function setTerminalToken($reference_id)
    {
        $transaction = EComProcessingCheckoutTransaction::getTransactionById($reference_id);

        $token = (isset($transaction['terminal_token']) && !empty($transaction['terminal_token'])
                    ? $transaction['terminal_token']
                    : null
        );

        if (empty($token)) {
            $reconcile = new \Genesis\Genesis('WPF\Reconcile');

            $reconcile->request()->setUniqueId($reference_id);

            $reconcile->execute();

            if ($reconcile->response()->isSuccessful()) {
                $token = $reconcile->response()->getResponseObject()->payment_transaction->terminal_token;
            }
        }

        \Genesis\Config::setToken(trim($token));
    }
}
