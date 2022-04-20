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

namespace EComprocessing;

class OrderTransactions
{
    /**
     * Build HTML with the additional resources to load on the OrderAdmin Page
     * @param \stdClass $data
     * @return string
     */
    private static function getResourcesHtml($data)
    {
        $zenCartVersion = ecp_get_zencart_version();
        $html = "";

        if (version_compare($zenCartVersion, "1.5.5", "<")) {
            $html .= ecp_add_external_resources(
                array(
                    "jquery-1.12.3.min.js",
                    "bootstrap.min.js",
                    "bootstrap.min.css",
                    "font-awesome.min.css"
                )
            );
        }

        $html .= ecp_add_external_resources(
            array(
                "bootstrapValidator.min.js",
                "treegrid/treegrid.min.js",
                "jquery.number.min.js",
                "treegrid/treegrid.min.css",
                "bootstrapValidator.min.css",
                "admin_order.css"
            )
        );

        return $html;
    }

    /**
     * Build HTML for the Order Admin PAge
     * @param \stdClass $data
     * @return string
     */
    private static function getHTML($data)
    {
        $module_name = $data->params['module_name'];
        ob_start();
        ?>
            <div class="panel-group" id="accordion">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a data-toggle="<?php echo $module_name;?>-collapse" data-target="#transactionsTable" href="javascript:void(1);">
                                <span class="ecomprocessing-logo">
                                    <?php echo $data->translations['panel']['title']; ?>
                                </span>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseOne" class="">
                        <table id="transactionsTable" class="table table-hover tree">
                            <thead>
                            <tr>
                                <?php
                                    $headerTranslations = $data->translations['panel']['transactions']['header'];
                                ?>
                                <th><?php echo $headerTranslations['id']; ?></th>
                                <th><?php echo $headerTranslations['type']; ?></th>
                                <th><?php echo $headerTranslations['timestamp']; ?></th>
                                <th><?php echo $headerTranslations['amount']; ?></th>
                                <th><?php echo $headerTranslations['status']; ?></th>
                                <th><?php echo $headerTranslations['message']; ?></th>
                                <th><?php echo $headerTranslations['mode']; ?></th>
                                <th><?php echo $headerTranslations['action_capture']; ?></th>
                                <th><?php echo $headerTranslations['action_refund']; ?></th>
                                <th><?php echo $headerTranslations['action_void']; ?></th>
                            </tr>
                            </thead>

                            <tbody>
                            <?php foreach ($data->transactions as $transaction) { ?>
                                <tr class="treegrid-<?php echo $transaction['unique_id'];?> <?php if(strlen($transaction['reference_id']) > 1): ?> treegrid-parent-<?php echo $transaction['reference_id'];?> <?php endif;?>">
                                    <td class="text-left">
                                        <?php echo $transaction['unique_id'];?>
                                    </td>
                                    <td class="text-left">
                                        <?php echo $transaction['type']; ?>
                                    </td>
                                    <td class="text-left">
                                        <?php echo $transaction['timestamp']; ?>
                                    </td>
                                    <td class="text-right">
                                        <?php echo $transaction['amount']; ?>
                                    </td>
                                    <td class="text-left">
                                        <?php echo $transaction['status']; ?>
                                    </td>
                                    <td class="text-left">
                                        <?php echo $transaction['message']; ?>
                                    </td>
                                    <td class="text-left">
                                        <?php echo $transaction['mode']; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($transaction['can_capture']) { ?>
                                            <div class="transaction-action-button">
                                                <a class="button btn btn-transaction btn-success" id="button-capture" role="button"
                                                   data-post-action="doCapture"
                                                   data-toggle="<?php echo $module_name;?>-tooltip" data-placement="bottom"
                                                   data-title="<?php echo $data->translations['modal']['capture']['title'];?>"
                                                   data-reference-id="<?php echo $transaction['unique_id'];?>"
                                                   data-amount="<?php echo $transaction['available_amount'];?>">
                                                    <i class="fa fa-check fa-lg"></i>
                                                </a>
                                            </div>
                                        <?php } ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($transaction['can_refund']) { ?>
                                            <div class="transaction-action-button">
                                                <a class="button btn btn-transaction btn-warning" id="button-refund" role="button"
                                                   data-post-action="doRefund"
                                                   data-toggle="<?php echo $module_name;?>-tooltip" data-placement="bottom"
                                                   title="<?php echo $data->translations['modal']['refund']['title'];?>"
                                                   data-reference-id="<?php echo $transaction['unique_id'];?>"
                                                   data-amount="<?php echo $transaction['available_amount'];?>">
                                                    <i class="fa fa-reply fa-lg"></i>
                                                </a>
                                            </div>
                                        <?php } ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($transaction['can_void']) { ?>
                                            <div class="transaction-action-button">
                                                <a class="button btn btn-transaction btn-danger" id="button-void" data-toggle="<?php echo $module_name;?>-tooltip" data-placement="bottom"
                                                    <?php if (!$data->params['modal']['void']['allowed']) { ?>
                                                         title="Cancel Transaction is currently disabled! <br /> This option can be enabled in the <strong>Module Settings</strong>, but it depends on the <strong>acquirer</strong>. For further Information please contact your <strong>Account Manager</strong>"
                                                    <?php } elseif ($transaction['void_exists']) { ?>
                                                        title="There is already an approved <strong>Cancel Transaction</strong> for <strong><?php echo ucfirst($transaction['type']);?> Transaction</strong> with Unique Id: <strong><?php echo $transaction['unique_id'];?></strong>"
                                                    <?php } ?>

                                                    <?php if (!$data->params['modal']['void']['allowed'] || $transaction['void_exists']) { ?>
                                                        disabled="disabled"
                                                    <?php } else { ?>
                                                        title="<?php echo $data->translations['modal']['void']['title'];?>"
                                                    <?php } ?>

                                                   role="button" data-post-action="doVoid" data-reference-id="<?php echo $transaction['unique_id'];?>">
                                                    <i class="fa fa-remove fa-lg"></i>
                                                </a>
                                                <span class="btn btn-primary" id="img_loading_void" style="display:none;">
                                                    <i class="fa fa-circle-o-notch fa-spin fa-lg"></i>
                                                </span>
                                            </div>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="<?php echo $module_name;?>-modal" class="modal fade">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                                <i class="icon-times"></i>
                            </button>
                            <span class="<?php echo $module_name;?>-modal-title ecomprocessing-logo"></span>
                        </div>
                        <div class="modal-body">
                            <?php
                            echo zen_draw_form($module_name . '-modal-form', FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=', 'post', 'class="modal-form" id="' . $module_name . '-modal-form"', true) . zen_hide_session_id();
                            ?>
                                <input type="hidden" name="reference_id" value="" />

                                <div id="<?php echo $module_name;?>_capture_trans_info" class="row" style="display: none;">
                                    <div class="col-xs-12">
                                        <div class="alert alert-info">
                                            You are allowed to process only full capture through this panel!
                                            <br/>
                                            This option can be enabled in the <strong>Module Settings</strong>, but it depends on the <strong>acquirer</strong>.
                                            For further Information please contact your <strong>Account Manager</strong>.
                                        </div>
                                    </div>
                                </div>

                                <div id="<?php echo $module_name;?>_refund_trans_info" class="row" style="display: none;">
                                    <div class="col-xs-12">
                                        <div class="alert alert-info">
                                            You are allowed to process only full refund through this panel!
                                            <br/>
                                            This option can be enabled in the <strong>Module Settings</strong>, but it depends on the <strong>acquirer</strong>.
                                            For further Information please contact your <strong>Account Manager</strong>.
                                        </div>
                                    </div>
                                </div>

                                <div id="<?php echo $module_name;?>_void_trans_info" class="row" style="display: none;">
                                    <div class="col-xs-12">
                                        <div class="alert alert-warning">
                                            This service is only available for particular acquirers!
                                            <br/>
                                            For further Information please contact your Account Manager.
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group amount-input">
                                    <label for="<?php echo $module_name;?>_transaction_amount">Amount:</label>
                                    <div class="input-group">
                                        <span class="input-group-addon" data-toggle="<?php echo $module_name;?>-tooltip" data-placement="top" title="<?php echo $data->params['currency']['iso_code'];?>"><?php echo $data->params['currency']['sign'];?></span>
                                        <input type="text" class="form-control" id="<?php echo $module_name;?>_transaction_amount" name="amount" placeholder="Amount..." />
                                    </div>
                                    <span class="help-block" id="<?php echo $module_name;?>-amount-error-container"></span>
                                </div>

                                <div class="form-group usage-input">
                                    <label for="<?php echo $module_name;?>_transaction_message">Message (optional):</label>
                                    <textarea class="form-control form-message" rows="3" id="<?php echo $module_name;?>_transaction_message" name="message" placeholder="Message"></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                        <span class="form-loading hidden">
                            <i class="icon-spinner icon-spin icon-large"></i>
                        </span>
                        <span class="form-buttons">
                            <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">Cancel</button>
                            <button id="<?php echo $module_name;?>-modal-submit" class="btn btn-submit btn-primary">Submit</button>
                        </span>
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
     * Build JS for the Order Admin Page
     * @param \stdClass $data
     * @return string
     */
    private static function getJS($data)
    {
        $module_name = $data->params['module_name'];
        ob_start();
        ?>
            <script type="text/javascript">
                var modalPopupDecimalValueFormatConsts = {
                    decimalPlaces       : <?php echo $data->params['currency']['decimalPlaces'];?>,
                    decimalSeparator    : "<?php echo $data->params['currency']['decimalSeparator'];?>",
                    thousandSeparator   : "<?php echo $data->params['currency']['thousandSeparator'];?>"
                };

                (function($) {
                    jQuery.exists = function(selector) {
                        return ($(selector).length > 0);
                    }
                }(window.jQuery));

                $(document).ready(function() {

                    jQuery("a[data-toggle='<?php echo $module_name;?>-collapse']").click(function() {
                        var targetId = jQuery(this).attr('data-target');

                        jQuery(targetId).toggle('slow');
                    });

                    jQuery(".tree").treegrid({
                        expanderExpandedClass:  "treegrid-expander-expanded",
                        expanderCollapsedClass: "treegrid-expander-collapsed"
                    });

                    $('.btn-transaction').click(function() {
                        if (jQuery(this).is('[disabled]'))
                            return ;

                        transactionModal($(this).attr('data-post-action'), $(this).attr('data-reference-id'), $(this).attr('data-amount'));
                    });

                    $('.btn-submit').click(function() {
                        var $submitForm = $('#<?php echo $module_name;?>-modal-form');
                        var submitFormAction = $submitForm.attr('action');
                        var submitFormPostAction = $submitForm.attr('data-post-action');
                        $submitForm.attr('action', submitFormAction + submitFormPostAction);

                        $submitForm.submit();
                    });

                    var modalObj = $('#<?php echo $module_name;?>-modal'),
                        transactionAmountInput = $('#<?php echo $module_name;?>_transaction_amount', modalObj);

                    modalObj.on('hide.bs.modal', function() {
                        destroyBootstrapValidator('#<?php echo $module_name;?>-modal-form');
                    });

                    modalObj.on('shown.bs.modal', function() {
                        /* enable the submit button just in case (if the bootstrapValidator is enabled it will disable the button if necessary */
                        $('#<?php echo $module_name;?>-modal-submit').removeAttr('disabled');

                        if (createBootstrapValidator('#<?php echo $module_name;?>-modal-form')) {
                            executeBootstrapFieldValidator('#<?php echo $module_name;?>-modal-form', 'fieldAmount');
                        }
                    });

                    transactionAmountInput.number(true, modalPopupDecimalValueFormatConsts.decimalPlaces,
                        modalPopupDecimalValueFormatConsts.decimalSeparator,
                        modalPopupDecimalValueFormatConsts.thousandSeparator);

                    $('[data-toggle="<?php echo $module_name;?>-tooltip"]').tooltip({
                        html: true
                    });
                });

                function transactionModal(post_action, reference_id, amount) {
                    if ((typeof amount == 'undefined') || (amount == null))
                        amount = 0;

                    var modalObj = $('#<?php echo $module_name;?>-modal');

                    var modalForm = $('#<?php echo $module_name;?>-modal-form', modalObj);

                    var modalTitle = modalObj.find('span.<?php echo $module_name;?>-modal-title'),
                        modalAmountInputContainer = modalObj.find('div.amount-input'),
                        captureTransactionInfoHolder = $('#<?php echo $module_name;?>_capture_trans_info', modalObj),
                        refundTransactionInfoHolder = $('#<?php echo $module_name;?>_refund_trans_info', modalObj),
                        cancelTransactionWarningHolder = $('#<?php echo $module_name;?>_void_trans_info', modalObj),
                        transactionAmountInput = $('#<?php echo $module_name;?>_transaction_amount', modalObj);

                    updateTransModalControlState([
                            captureTransactionInfoHolder,
                            refundTransactionInfoHolder,
                            cancelTransactionWarningHolder,
                            modalAmountInputContainer
                        ],
                        false
                    );

                    switch(post_action) {
                        case 'doCapture':
                            modalTitle.text('<?php echo $data->translations['modal']['capture']['title'];?>');
                            updateTransModalControlState([modalAmountInputContainer], true);
                            <?php if (!$data->params['modal']['capture']['allowed']) { ?>
                                updateTransModalControlState([captureTransactionInfoHolder], true);
                                transactionAmountInput.attr('readonly', 'readonly');
                            <?php } else { ?>
                                transactionAmountInput.removeAttr('readonly');
                            <?php } ?>
                            break;

                        case 'doRefund':
                            modalTitle.text('<?php echo $data->translations['modal']['refund']['title'];?>');
                            updateTransModalControlState([modalAmountInputContainer], true);
                            <?php if (!$data->params['modal']['refund']['allowed']) { ?>
                                updateTransModalControlState([refundTransactionInfoHolder], true);
                                transactionAmountInput.attr('readonly', 'readonly');
                            <?php } else { ?>
                                transactionAmountInput.removeAttr('readonly');
                            <?php } ?>
                            break;

                        case 'doVoid':
                            modalTitle.text('<?php echo $data->translations['modal']['void']['title'];?>');
                            <?php if (!$data->params['modal']['void']['allowed']) { ?>
                                updateTransModalControlState([cancelTransactionWarningHolder], true);
                            <?php } ?>
                            break;

                        default:
                            return;
                    }

                    modalObj.find('input[name="reference_id"]').val(reference_id);

                    modalForm.attr('data-post-action', post_action);

                    transactionAmountInput.val(amount);

                    modalObj.modal('show');

                }

                function updateTransModalControlState(controls, visibilityStatus) {
                    $.each(controls, function(index, control){
                        if (!$.exists(control))
                            return; /* continue to the next item */

                        if (visibilityStatus)
                            control.fadeIn('fast');
                        else
                            control.fadeOut('fast');
                    });
                }

                function formatTransactionAmount(amount) {
                    if ((typeof amount == 'undefined') || (amount == null))
                        amount = 0;
                    return $.number(amount, modalPopupDecimalValueFormatConsts.decimalPlaces,
                        modalPopupDecimalValueFormatConsts.decimalSeparator,
                        modalPopupDecimalValueFormatConsts.thousandSeparator);
                }

                function executeBootstrapFieldValidator(formId, validatorFieldName) {
                    var submitForm = $(formId);
                    submitForm.bootstrapValidator('validateField', validatorFieldName);
                    submitForm.bootstrapValidator('updateStatus', validatorFieldName, 'NOT_VALIDATED');
                }

                function destroyBootstrapValidator(submitFormId) {
                    $(submitFormId).bootstrapValidator('destroy');
                }
                function createBootstrapValidator(submitFormId) {
                    var submitForm = $(submitFormId),
                        transactionAmount = formatTransactionAmount($('#<?php echo $module_name;?>_transaction_amount').val());
                    destroyBootstrapValidator(submitFormId);

                    var transactionAmountControlSelector = '#<?php echo $module_name;?>_transaction_amount';

                    var shouldCreateValidator = $.exists(transactionAmountControlSelector);

                    /* it is not needed to create attach the bootstapValidator,
                    when the field to validate is not visible (Void Transaction) */
                    if (!shouldCreateValidator) {
                        return false;
                    }

                    submitForm.bootstrapValidator({
                            fields: {
                                fieldAmount: {
                                    selector: transactionAmountControlSelector,
                                    trigger: 'keyup',
                                    validators: {
                                        notEmpty: {
                                            message: 'The transaction amount is a required field!'
                                        },
                                        stringLength: {
                                            max: 10
                                        },
                                        greaterThan: {
                                            value: 0,
                                            inclusive: false
                                        },
                                        lessThan: {
                                            value: transactionAmount,
                                            inclusive: true
                                        }
                                    }
                                }
                            }
                        })
                        .on('error.field.bv', function(e, data) {
                            $('#<?php echo $module_name;?>-modal-submit').attr('disabled', 'disabled');
                        })
                        .on('success.field.bv', function(e) {
                            $('#<?php echo $module_name;?>-modal-submit').removeAttr('disabled');
                        })
                        .on('success.form.bv', function(e) {
                            e.preventDefault(); // Prevent the form from submitting
                            /* submits the transaction form (No validators have failed) */
                            submitForm.bootstrapValidator('defaultSubmit');
                        });

                    return true;
                }

            </script>
        <?php
        $js = ob_get_contents();
        ob_end_clean();
        return $js;
    }

    /**
     * Build Complete Order Admin Transactions Panel Content
     * @param \stdClass $data
     * @return bool|string
     */
    public static function printOrderTransactions($data)
    {
        if (count($data->transactions) == 0) {
            return false;
        }

        $resourcesHTML = static::getResourcesHtml($data);
        $html = static::getHTML($data);
        $js = static::getJS($data);

        $outputStartBlock = '<td><table class="noprint">'."\n";
        $outputStartBlock .= '<tr style="background-color : #bbbbbb; border-style : dotted;">'."\n";
        $outputEndBlock = '</tr>'."\n";
        $outputEndBlock .='</table></td>'."\n";

        return
            $outputStartBlock .
            $resourcesHTML .
            $html .
            $js .
            $outputEndBlock;
    }
}
