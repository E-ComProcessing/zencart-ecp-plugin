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

namespace Ecomprocessing\Checkout;

use \Ecomprocessing\Checkout\Settings as EcomprocessingCheckoutSettings;
use \Ecomprocessing\Checkout\Transaction as EcomprocessingCheckoutTransaction;
use Ecomprocessing\Helpers\ThreedsHelper;
use Ecomprocessing\Helpers\TransactionsHelper;
use Genesis\API\Constants\Payment\Methods;
use Genesis\API\Constants\Transaction\Parameters\PayByVouchers\CardTypes;
use Genesis\API\Constants\Transaction\Parameters\PayByVouchers\RedeemTypes;
use Genesis\API\Constants\Transaction\Types;

class TransactionProcess extends \Ecomprocessing\Base\TransactionProcess
{
    const ORDER_CONTENT_TYPE_VIRTUAL = 'virtual';

    /**
     * Set Genesis Config Values (Ex. Login, Password, Token, etc)
     */
    protected static function doLoadGenesisPrivateConfigValues()
    {
        parent::doLoadGenesisPrivateConfigValues();

        if (EcomprocessingCheckoutSettings::getIsConfigured()) {
            \Genesis\Config::setUsername(
                EcomprocessingCheckoutSettings::getUserName()
            );

            \Genesis\Config::setPassword(
                EcomprocessingCheckoutSettings::getPassword()
            );

            \Genesis\Config::setEnvironment(
                EcomprocessingCheckoutSettings::getIsLiveMode()
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

            /**
             * Autocomplete helper comment
             *
             * @var \Genesis\API\Request\WPF\Create $request
             */
            $request = $genesis->request();
            $request
                ->setTransactionId($data->transaction_id)
                ->setUsage(self::getUsage())
                ->setDescription($data->description)
                ->setNotificationUrl($data->urls['notification'])
                ->setReturnSuccessUrl($data->urls['return_success'])
                ->setReturnPendingUrl($data->urls['return_success'])
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
                ->setBillingState(self::getStateCode($data->order->billing))
                ->setBillingCountry($data->order->billing['country']['iso_code_2'])
                ->setShippingFirstName($data->order->delivery['firstname'])
                ->setShippingLastName($data->order->delivery['lastname'])
                ->setShippingAddress1($data->order->delivery['street_address'])
                ->setShippingZipCode($data->order->delivery['postcode'])
                ->setShippingCity($data->order->delivery['city'])
                ->setShippingState(self::getStateCode($data->order->delivery))
                ->setShippingCountry($data->order->delivery['country']['iso_code_2'])
                ->setLanguage($data->language_id);

            static::_addTransactionTypesToGatewayRequest($genesis, $data->order);

            static::setTokenizationData($genesis->request());

            if (EcomprocessingCheckoutSettings::isThreedsAlowed()) {
                static::_addThreedsData($genesis, $data);
            }

            $wpfAmount = (float)$request->getAmount();
            if ($wpfAmount <= Settings::getScaExemptionAmount()) {
                $request->setScaExemption(
                    Settings::getScaExemption()
                );
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
     * Add transaction types to the WPF Request
     *
     * @param \Genesis\Genesis $genesis Genesis Request
     * @param array            $order   Order Attributes
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return void
     * @throws \Genesis\Exceptions\ErrorParameter
     */
    private static function _addTransactionTypesToGatewayRequest($genesis, $order)
    {
        $types = static::_getCheckoutTransactionTypes();

        foreach ($types as $transactionType) {
            if (is_array($transactionType)) {
                $genesis
                    ->request()
                    ->addTransactionType(
                        $transactionType['name'],
                        $transactionType['parameters']
                    );

                continue;
            }

            $parameters = static::_getCustomRequiredAttributes(
                $transactionType,
                $order
            );

            $genesis
                ->request()
                ->addTransactionType(
                    $transactionType,
                    $parameters
                );

            unset($parameters);
        }
    }

    /**
     * Retrieve custom required attributes for specific transaction type
     *
     * @param string $transactionType Transaction Type
     * @param array  $order           Current order for the session
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return array
     * @throws \Genesis\Exceptions\ErrorParameter
     */
    private static function _getCustomRequiredAttributes($transactionType, $order)
    {
        $parameters = array();

        switch ($transactionType) {
        case \Genesis\API\Constants\Transaction\Types::PAYBYVOUCHER_SALE:
            $parameters = array(
                'card_type'   =>
                    CardTypes::VIRTUAL,
                'redeem_type' =>
                    RedeemTypes::INSTANT
            );
            break;
        case \Genesis\API\Constants\Transaction\Types::IDEBIT_PAYIN:
        case \Genesis\API\Constants\Transaction\Types::INSTA_DEBIT_PAYIN:
            $parameters = array(
                'customer_account_id' => static::getCurrentUserIdHash()
            );
            break;
        case \Genesis\API\Constants\Transaction\Types::KLARNA_AUTHORIZE:
            $items      = TransactionsHelper::getKlarnaCustomParamItems($order);
            $parameters = $items->toArray();
            break;
        case \Genesis\API\Constants\Transaction\Types::TRUSTLY_SALE:
            $userId = static::getCustomerId();
            $trustlyUserId = empty($userId) ?
                static::getCurrentUserIdHash() : $userId;

            $parameters = array(
                'user_id' => $trustlyUserId
            );
            break;
        case Types::ONLINE_BANKING_PAYIN:
            $selectedBankCodes = array_filter(
                Settings::getSelectedBankCodes(),
                function ($value) {
                    return $value != 'none';
                }
            );
            if (\Genesis\Utils\Common::isValidArray($selectedBankCodes)) {
                $parameters['bank_codes'] = array_map(
                    function ($value) {
                        return ['bank_code' => $value];
                    },
                    $selectedBankCodes
                );
            }
            break;
        case \Genesis\API\Constants\Transaction\Types::PAYSAFECARD:
            $userId = static::getCustomerId();
            $customerId = empty($userId) ?
                static::getCurrentUserIdHash() : $userId;

            $parameters = array(
                'customer_id' => $customerId
            );
            break;
        }

        return $parameters;
    }

    /**
     * Get Available Checkout Transaction Types
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return array
     */
    private static function _getCheckoutTransactionTypes()
    {
        $processedList = array();
        $aliasMap      = array();

        $selectedTypes = EcomprocessingCheckoutSettings::getTransactionTypes();

        $pproSuffix    = PPRO_TRANSACTION_SUFFIX;
        $methods       = Methods::getMethods();

        foreach ($methods as $method) {
            $aliasMap[$method . $pproSuffix] = Types::PPRO;
        }

        $aliasMap = array_merge(
            $aliasMap,
            [
                GOOGLE_PAY_TRANSACTION_PREFIX . GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE =>
                    Types::GOOGLE_PAY,
                GOOGLE_PAY_TRANSACTION_PREFIX . GOOGLE_PAY_PAYMENT_TYPE_SALE      =>
                    Types::GOOGLE_PAY,
                PAYPAL_TRANSACTION_PREFIX . PAYPAL_PAYMENT_TYPE_AUTHORIZE         =>
                    Types::PAY_PAL,
                PAYPAL_TRANSACTION_PREFIX . PAYPAL_PAYMENT_TYPE_SALE              =>
                    Types::PAY_PAL,
                PAYPAL_TRANSACTION_PREFIX . PAYPAL_PAYMENT_TYPE_EXPRESS           =>
                    Types::PAY_PAL,
                APPLE_PAY_TRANSACTION_PREFIX . APPLE_PAY_PAYMENT_TYPE_AUTHORIZE   =>
                    Types::APPLE_PAY,
                APPLE_PAY_TRANSACTION_PREFIX . APPLE_PAY_PAYMENT_TYPE_SALE        =>
                    Types::APPLE_PAY,
            ]
        );

        foreach ($selectedTypes as $selectedType) {
            if (array_key_exists($selectedType, $aliasMap)) {
                $transactionType = $aliasMap[$selectedType];

                $processedList[$transactionType]['name'] = $transactionType;

                $key = self::_getCustomParameterKey($transactionType);

                $processedList[$transactionType]['parameters'][] = array(
                    $key => str_replace(
                        [
                            $pproSuffix,
                            GOOGLE_PAY_TRANSACTION_PREFIX,
                            PAYPAL_TRANSACTION_PREFIX,
                            APPLE_PAY_TRANSACTION_PREFIX
                        ],
                        '',
                        $selectedType
                    )
                );
            } else {
                $processedList[] = $selectedType;
            }
        }

        return $processedList;
    }

    /**
     * @param \stdClass $request Genesis request
     *
     * @return void
     */
    protected static function setTokenizationData($request)
    {
        $consumer = static::getConsumerFromDb();

        if ($consumer !== false
            && $consumer['customer_id'] != static::getCustomerId()
        ) {
            static::redirectToShowError();
        }

        if ($consumer === false) {
            $consumer_id = static::getConsumerIdFromGenesisGateway();

            if ($consumer_id !== 0) {
                static::saveConsumerId($consumer_id);
            }
        } else {
            $consumer_id = $consumer['consumer_id'];
        }

        if (!empty($consumer_id)) {
            $request->setConsumerId($consumer_id);
        }

        if (EcomprocessingCheckoutSettings::isWpfTokenizationEnabled()) {
            $request->setRememberCard(true);
        }
    }

    /**
     * Show error after being redirected
     *
     * @return void
     */
    protected static function redirectToShowError()
    {
        global $messageStack;

        $messageStack->add_session(
            'checkout_payment',
            'Cannot process your request, please contact the administrator.',
            'error'
        );
        zen_redirect(
            zen_href_link(
                FILENAME_CHECKOUT_PAYMENT,
                'payment_error=Ecomprocessing_checkout',
                'SSL'
            )
        );
    }

    /**
     * Use Genesis API to get consumer ID
     *
     * @return int
     */
    protected static function getConsumerIdFromGenesisGateway()
    {
        global $order;

        try {
            $genesis = new \Genesis\Genesis('NonFinancial\Consumers\Retrieve');
            $genesis->request()->setEmail($order->customer['email_address']);

            $genesis->execute();

            $response = $genesis->response()->getResponseObject();

            if (static::isErrorResponse($response)) {
                return 0;
            }

            return intval($response->consumer_id);
        } catch (\Exception $exception) {
            return 0;
        }
    }

    /**
     * Checks if Genesis response is an error
     *
     * @param \stdClass $response Genesis response
     *
     * @return bool
     */
    protected static function isErrorResponse($response)
    {
        $state = new \Genesis\API\Constants\Transaction\States($response->status);

        return $state->isError();
    }

    /**
     * Save consumer ID to DB
     *
     * @param int $consumer_id Consumer ID
     *
     * @return bool
     */
    public static function saveConsumerId($consumer_id)
    {
        global $db, $order;

        if (empty($order->customer['email_address']) || empty($consumer_id)
            || static::getCustomerId() === 0
        ) {
            return false;
        }

        $consumer = static::getConsumerFromDb();

        if ($consumer !== false) {
            return false;
        }

        $sql = '
            INSERT INTO `' . TABLE_ECOMPROCESSING_CHECKOUT_CONSUMERS . '` (
                `customer_id`,
                `customer_email`,
                `consumer_id`
            )
            VALUES (
                :customer_id,
                :customer_email,
                :consumer_id
            )
        ';
        $sql = $db->bindVars(
            $sql,
            ':customer_id',
            static::getCustomerId(),
            'integer'
        );
        $sql = $db->bindVars(
            $sql,
            ':customer_email',
            $order->customer['email_address'],
            'string'
        );
        $sql = $db->bindVars(
            $sql,
            ':consumer_id',
            intval($consumer_id),
            'integer'
        );

        $db->Execute($sql);

        return true;
    }

    /**
     * Get logged customer's ID
     *
     * @return int
     *
     * @SuppressWarnings(PHPMD)
     */
    public static function getCustomerId()
    {
        return intval($_SESSION['customer_id']);
    }

    /**
     * Generate unique hash from user Id
     *
     * @param int $length Hash Length
     *
     * @return string
     */
    public static function getCurrentUserIdHash($length = 30)
    {
        $userId = self::getCurrentUserIdHash();

        $userHash = $userId > 0 ? sha1($userId) : md5(uniqid() . microtime(true));

        return substr($userHash, 0, $length);
    }

    /**
     * Get consumer from DB
     *
     * @return array|bool
     */
    public static function getConsumerFromDb()
    {
        global $db, $order;

        $query = $db->Execute(
            $db->bindVars(
                'SELECT
                    *
                  FROM
                    `' . TABLE_ECOMPROCESSING_CHECKOUT_CONSUMERS . '`
                  WHERE
                    `customer_email` = :customer_email',
                ':customer_email',
                $order->customer['email_address'],
                'string'
            ),
            false, // zf_limit
            false, // zf_cache
            0,     // zf_cachetime
            true   // remove_from_queryCache
        );

        if ($query->RecordCount() !== 1) {
            return false;
        }

        return $query->fields;
    }

    /**
     * Set Genesis Terminal Token
     *
     * @param string $reference_id Reference ID
     *
     * @throws \Genesis\Exceptions\ErrorAPI
     * @throws \Genesis\Exceptions\InvalidArgument
     * @throws \Genesis\Exceptions\InvalidMethod
     * @throws \Genesis\Exceptions\InvalidResponse
     */
    public static function setTerminalToken($reference_id)
    {
        $transaction = EcomprocessingCheckoutTransaction::getTransactionById(
            $reference_id
        );

        $token = (isset($transaction['terminal_token'])
        && !empty($transaction['terminal_token'])
            ? $transaction['terminal_token']
            : null
        );

        if (empty($token)) {
            $reconcile = new \Genesis\Genesis('WPF\Reconcile');

            $reconcile->request()->setUniqueId($reference_id);

            $reconcile->execute();

            if ($reconcile->response()->isSuccessful()) {
                $token = $reconcile
                    ->response()
                    ->getResponseObject()
                    ->payment_transaction->terminal_token;
            }
        }

        \Genesis\Config::setToken(trim($token));
    }

    /**
     * Returns payment method/type based on transaction type
     *
     * @param string $transactionType Transaction type
     *
     * @return string
     */
    private static function _getCustomParameterKey($transactionType)
    {
        switch ($transactionType) {
        case Types::PPRO:
            $result = 'payment_method';
            break;
        case Types::PAY_PAL:
            $result = 'payment_type';
            break;
        case Types::GOOGLE_PAY:
        case Types::APPLE_PAY:
            $result = 'payment_subtype';
            break;
        default:
            $result = 'unknown';
        }

        return $result;
    }

    /**
     * Add optional 3DSv2 parameters
     *
     * @param object $genesis Genesis request object
     * @param object $data    Order data object
     *
     * @return void
     *
     * @throws \Genesis\Exceptions\InvalidArgument
     */
    private static function _addThreedsData($genesis, $data)
    {
        global $db;

        $order         = $data->order;
        $customerId    = self::getCustomerId();
        $isVirtualCart = $order->content_type == self::ORDER_CONTENT_TYPE_VIRTUAL;

        $customerInfo     = ThreedsHelper::getCustomerInfo($customerId, $db);
        $customerOrders   = ThreedsHelper::getCustomerOrders($customerId, $db);
        $ordersForPeriod  = ThreedsHelper::findNumberOfOrdersForPeriod(
            $customerOrders
        );

        /**
         * Autocomplete helper comment
         *
         * @var \Genesis\API\Request\WPF\Create $request
         */
        $request = $genesis->request();

        $request
            ->setThreedsV2ControlChallengeIndicator(
                EcomprocessingCheckoutSettings::getChallengeIndicator()
            )
            ->setThreedsV2PurchaseCategory(
                ThreedsHelper::getThreedsPurchaseCategory($isVirtualCart)
            )
            ->setThreedsV2MerchantRiskDeliveryTimeframe(
                ThreedsHelper::getThreedsDeliveryTimeframe($isVirtualCart)
            )
            ->setThreedsV2MerchantRiskShippingIndicator(
                ThreedsHelper::getShippingIndicator($data, $isVirtualCart)
            )
            ->setThreedsV2MerchantRiskReorderItemsIndicator(
                ThreedsHelper::getReorderItemsIndicator(
                    $customerId, $data->order->products, $db
                )
            )
            ->setThreedsV2CardHolderAccountCreationDate(
                $customerInfo['date_account_created']
            )
            ->setThreedsV2CardHolderAccountPasswordChangeDate(
                $customerInfo['date_account_last_modified']
            )
            ->setThreedsV2CardHolderAccountPasswordChangeIndicator(
                ThreedsHelper::getPasswordChangeIndicator(
                    $customerInfo['date_account_last_modified']
                )
            )
            ->setThreedsV2CardHolderAccountLastChangeDate(
                $customerInfo['date_account_last_modified']
            )
            ->setThreedsV2CardHolderAccountUpdateIndicator(
                ThreedsHelper::getUpdateIndicator($customerInfo)
            )
            ->setThreedsV2CardHolderAccountRegistrationDate(
                ThreedsHelper::findFirstCustomerOrderDate($customerOrders)
            )
            ->setThreedsV2CardHolderAccountRegistrationIndicator(
                ThreedsHelper::getRegistrationIndicator($customerOrders)
            )
            ->setThreedsV2CardHolderAccountTransactionsActivityLast24Hours(
                $ordersForPeriod['last_24h']
            )
            ->setThreedsV2CardHolderAccountTransactionsActivityPreviousYear(
                $ordersForPeriod['last_year']
            )
            ->setThreedsV2CardHolderAccountPurchasesCountLast6Months(
                $ordersForPeriod['last_6m']
            );

        if (!$isVirtualCart) {
            $shippingAddressDateFirstUsed
                = ThreedsHelper::findShippingAddressDateFirstUsed(
                    $data->order->delivery,
                    $customerOrders
                );

            $request
                ->setThreedsV2CardHolderAccountShippingAddressDateFirstUsed(
                    $shippingAddressDateFirstUsed
                )
                ->setThreedsV2CardHolderAccountShippingAddressUsageIndicator(
                    ThreedsHelper::getShippingAddressUsageIndicator(
                        $shippingAddressDateFirstUsed
                    )
                );
        }
    }
}
