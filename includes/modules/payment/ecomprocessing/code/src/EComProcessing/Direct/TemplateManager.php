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

namespace EComProcessing\Direct;

abstract class TemplateManager
{

    /**
     * Construct HTML for the Card Payment on the Checkout Page
     * @param array $data
     * @return string
     */
    public static function getCardHTMLContent($data)
    {
        ob_start();
    ?>
        <div class="transparent-redirect-box">
            <div id="payment-method-ecomprocessing-direct">

                <div class="payment-method-container">
                    <div class="payment-method-header">
                        <div class="row no-gutter">
                            <div class="col-xs-12">
                                <h2><?php echo $data['title'];?></h2>
                            </div>
                        </div>
                    </div>

                    <div class="payment-method-content">
                        <div class="row no-gutter">
                            <div class="card-wrapper-container no-gutter">
                                <div class="card-wrapper"></div>
                            </div>

                            <div class="card-controls-container no-gutter">
                                <div class="form-wrapper">
                                    <div class="form-group active">
                                        <input autocomplete="off"
                                               placeholder="<?php echo $data['card_controls']['card_number']['placeholder'];?>"
                                               title="<?php echo $data['card_controls']['card_number']['title'];?>"
                                               class="form-control field-required" type="text"
                                               id="<?php echo $data['card_controls']['card_number']['name'];?>"
                                               name="<?php echo $data['card_controls']['card_number']['name'];?>">
                                        <input autocomplete="off"
                                               placeholder="<?php echo $data['card_controls']['card_holder']['placeholder'];?>"
                                               title="<?php echo $data['card_controls']['card_holder']['title'];?>"
                                               class="form-control field-required" type="text"
                                               id="<?php echo $data['card_controls']['card_holder']['name'];?>"
                                               name="<?php echo $data['card_controls']['card_holder']['name'];?>">
                                        <input autocomplete="off"
                                               placeholder="<?php echo $data['card_controls']['card_cvv']['placeholder'];?>"
                                               title="<?php echo $data['card_controls']['card_cvv']['title'];?>"
                                               class="form-control card-cvv field-required" type="text"
                                               id="<?php echo $data['card_controls']['card_cvv']['name'];?>"
                                               name="<?php echo $data['card_controls']['card_cvv']['name'];?>">
                                        <input autocomplete="off"
                                               placeholder="<?php echo $data['card_controls']['card_expiry']['placeholder'];?>"
                                               title="<?php echo $data['card_controls']['card_expiry']['title'];?>"
                                               class="form-control card-expiry field-required" type="text"
                                               id="<?php echo $data['card_controls']['card_expiry']['name'];?>"
                                               name="<?php echo $data['card_controls']['card_expiry']['name'];?>">

                                        <input type="hidden"
                                               id="<?php echo $data['hidden']['expiryMonth'];?>"
                                               name="<?php echo $data['hidden']['expiryMonth'];?>"/>
                                        <input type="hidden"
                                               id="<?php echo $data['hidden']['expiryYear'];?>"
                                               name="<?php echo $data['hidden']['expiryYear'];?>"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-1 col-lg-2"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    <?php
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    /**
     * Construct JS for the Card Payment on the Checkout Page
     * @param array $data
     * @return string
     */
    public static function getCardJSContent($data)
    {
        $cardJSFileName =
            DIR_FS_CATALOG .
            DIR_WS_INCLUDES .
            "modules/payment/ecomprocessing/private/resources/js/card.min.js";

        if (file_exists($cardJSFileName)) {
            $cardJSContent = file_get_contents($cardJSFileName);
        } else {
            $cardJSContent = false;
        }

        if ($cardJSContent === false) {
            return false;
        }

        ob_start()
        ?>
            <script type="text/javascript">
                <?php echo $cardJSContent; ?>
            </script>
        <?php
        $cardJSContent = ob_get_contents();
        ob_end_clean();
        ob_start();
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function() {
                attachCardToWrapper()

                jQuery('<?php echo $data['formSelectors']['expiryInput'];?>').keyup(function() {
                    var ccExpiresMonthYear = jQuery(this).val().split(' / ');

                    if (ccExpiresMonthYear.length == 2) {
                        jQuery('#<?php echo $data['hidden']['expiryMonth'];?>').val(ccExpiresMonthYear[0]);
                        jQuery('#<?php echo $data['hidden']['expiryYear'];?>').val(ccExpiresMonthYear[1]);
                    }
                });

            });

            function attachCardToWrapper() {
                var cardWrapper = jQuery('<?php echo $data['container'];?>');

                if (cardWrapper.length) {
                    new Card({
                        form: '<?php echo $data['form'];?>',
                        container: '<?php echo $data['container'];?>',
                        formSelectors: {
                            nameInput: '<?php echo $data['formSelectors']['nameInput'];?>',
                            numberInput: '<?php echo $data['formSelectors']['numberInput'];?>',
                            cvcInput: '<?php echo $data['formSelectors']['cvcInput'];?>',
                            expiryInput: '<?php echo $data['formSelectors']['expiryInput'];?>'
                        }
                    });
                }
            }
        </script>
    <?php
        $customJS = ob_get_contents();
        ob_end_clean();

        return
            $cardJSContent .
            $customJS;
    }

    /**
     * Construct CSS for the Card Payment on the Checkout Page
     * @return string
     */
    public static function getCardStyleContent()
    {
        ob_start();
    ?>
        <style type="text/css">
            #payment-method-ecomprocessing-direct {
                padding-top: 16px !important;
            }

            #payment-method-ecomprocessing-direct .payment-method-container {
                background: #FFF;
                border: 1px solid #ddd;
                border-radius: 16px;
                padding: 22px 12px;
                overflow: hidden;
                margin: 0px;
            }

            #payment-method-ecomprocessing-direct .payment-method-container .no-gutter {
                margin: 0;
                padding: 0;
            }

            #payment-method-ecomprocessing-direct .payment-method-container .payment-method-status .row-spacer {
                margin-bottom: 16px;
            }

            #payment-method-ecomprocessing-direct .payment-method-container input {
                -webkit-box-sizing: border-box;
                -moz-box-sizing: border-box;
                box-sizing: border-box;
                max-width: 350px !important;
            }

            #payment-method-ecomprocessing-direct .payment-method-container .payment-method-content .card-wrapper {
                display: block;
                padding-top: 16px;
                margin-bottom: 16px;
            }

            #payment-method-ecomprocessing-direct .payment-method-container .payment-method-content .form-wrappe .form-group {
                margin: 16px auto 0 auto;
            }

            #payment-method-ecomprocessing-direct .payment-method-container .payment-method-content .form-wrapper .form-group input {
                width: 100%;
                margin: 0 auto 16px auto;
                height: 36px;
                padding: 0 8px;
            }

            #payment-method-ecomprocessing-direct .payment-method-container .payment-method-content .form-wrapper .form-group input.submit {
                box-shadow: none;
                border-radius: 6px !important;
                background: #5F604B;
                color: #fff;
                height: 40px;
                line-height: 8px;
                cursor: pointer;
                cursor: hand;
                margin: 0 auto;
            }

            #payment-method-ecomprocessing-direct .payment-method-container .payment-method-content .form-wrapper .form-group input.submit:hover {
                text-decoration: underline;
            }

            .jp-card {
                min-width: 250px !important;
                margin: 0px;
            }

            .jp-card .jp-card-front {
                margin: 0px;
            }

            .card-wrapper-container, .card-controls-container {
                float: left;
                width: 100%;
                text-align: center;
            }
        </style>
    <?php
        $css = ob_get_contents();
        ob_end_clean();

        return $css;
    }

    public static function getPostedCCInfo($requestData)
    {
        $ccInfo = array(
            'cc_number' => $requestData[ECOMPROCESSING_DIRECT_CODE . '_cc_number'],
            'cc_owner'  => $requestData[ECOMPROCESSING_DIRECT_CODE . '_cc_owner'],
            'cc_cvv'    => $requestData[ECOMPROCESSING_DIRECT_CODE . '_cc_cvv']
        );

        if (isset($requestData[ECOMPROCESSING_DIRECT_CODE . '_cc_expires_month']) &&
            isset($requestData[ECOMPROCESSING_DIRECT_CODE . '_cc_expires_year'])) {
            $ccInfo['cc_expires_month'] = $requestData[ECOMPROCESSING_DIRECT_CODE . '_cc_expires_month'];
            $ccInfo['cc_expires_year'] = $requestData[ECOMPROCESSING_DIRECT_CODE . '_cc_expires_year'];
        } elseif (isset($requestData[ECOMPROCESSING_DIRECT_CODE . '_cc_expires'])) {
            list ($expiryMonth, $expiryYear) = explode(' / ', $requestData[ECOMPROCESSING_DIRECT_CODE . '_cc_expires']);
            $ccInfo['cc_expires_month'] = $expiryMonth;
            $ccInfo['cc_expires_year'] = $expiryYear;
        }

        return $ccInfo;
    }
}