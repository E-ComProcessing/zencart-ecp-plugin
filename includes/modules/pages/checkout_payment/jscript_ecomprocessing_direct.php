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

if (!class_exists('Ecomprocessing\Direct\TemplateManager')) {
    return;
}

if (\Ecomprocessing\Direct\Settings::getIsAvailableOnCheckoutPage() &&
    \Ecomprocessing\Direct\Settings::getShouldUseIntegratedPaymentTemplate()
) {
    echo \Ecomprocessing\Direct\TemplateManager::getCardJSContent(
        array(
            'form' => 'form[name="checkout_payment"]',
            'container' => '#payment-method-ecomprocessing-direct .card-wrapper',
            'formSelectors' => array(
                'nameInput' => 'input[name="' . ECOMPROCESSING_DIRECT_CODE . '_cc_owner"]',
                'numberInput' => 'input[name="' . ECOMPROCESSING_DIRECT_CODE . '_cc_number"]',
                'cvcInput' =>'input[name="' . ECOMPROCESSING_DIRECT_CODE . '_cc_cvv"]',
                'expiryInput' => 'input[name="' . ECOMPROCESSING_DIRECT_CODE . '_cc_expires"]'
            ),
            'hidden' => array(
                'expiryMonth' => ECOMPROCESSING_DIRECT_CODE . '_cc_expires_month',
                'expiryYear' => ECOMPROCESSING_DIRECT_CODE . '_cc_expires_year'
            )
        )
    );

    echo \Ecomprocessing\Direct\TemplateManager::getCardStyleContent();
}
