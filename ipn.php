<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * shurjopay instant payments notifications page
 *
 * @package    paygw_shurjopay
 * @copyright  2022 Brain station 23 ltd.
 * @author     Brain station 23 ltd.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_shurjopay\shurjopay_helper;

require_once(__DIR__ . '/../../../config.php');
global $DB, $CFG, $USER;

require_login();

$order_id = required_param('order_id', PARAM_TEXT);

global $DB;

$config_data = $DB->get_record('payment_gateways', ['gateway' => 'shurjopay']);
$config= json_decode($config_data->config);

$shurjopayhelper = new shurjopay_helper(
    $config->username,
    $config->password,
    $config->paymentmodes
);

$result = $shurjopayhelper->generate_token();
$varify_data = $shurjopayhelper->varify_payment($result['token'], $order_id);

$data = new stdClass();
$data->txn_id = $varify_data[0]['id'];
$data->order_id = $varify_data[0]['order_id'];
$data->currency = $varify_data[0]['currency'];
$data->amount = $varify_data[0]['amount'];
$data->payable_amount = $varify_data[0]['payable_amount'];
$data->received_amount = $varify_data[0]['received_amount'];
$data->card_holder_name = $varify_data[0]['card_holder_name'];
$data->bank_trx_id = $varify_data[0]['bank_trx_id'];
$data->invoice_no = $varify_data[0]['invoice_no'];
$data->bank_status = $varify_data[0]['bank_status'];
$data->customer_order_id = $varify_data[0]['customer_order_id'];
$data->sp_message = $varify_data[0]['sp_message'];
$data->sp_code = $varify_data[0]['sp_code'];
$data->userid = $USER->id;
$data->name = $varify_data[0]['name'];
$data->email = $varify_data[0]['email'];
$data->address = $varify_data[0]['address'];
$data->city = $varify_data[0]['city'];
$data->city = $varify_data[0]['city'];
$data->method = $varify_data[0]['date_time'];
$data->component = $varify_data[0]['value1'];
$data->itemid = $varify_data[0]['value4'];
$data->paymentarea = $varify_data[0]['value3'];

$DB->insert_record('paygw_shurjopay_log', $data);

    // Course enrollment.
    if ($data->bank_status == "Success") {
        redirect($CFG->wwwroot .
            '/payment/gateway/shurjopay/success.php?component=' . $data->component . '&paymentarea=' . $data->paymentarea .
            '&itemid=' . $data->itemid);
        exit();
    }   elseif ($data->bank_status == "Failed") {
        redirect($CFG->wwwroot .
            '/payment/gateway/shurjopay/fail.php?component=' . $data->component . '&paymentarea=' . $data->paymentarea .
            '&itemid=' . $data->itemid);
        exit();
    } else {
        redirect($CFG->wwwroot .
            '/payment/gateway/shurjopay/cancel.php?component=' . $data->component . '&paymentarea=' . $data->paymentarea .
            '&itemid=' . $data->itemid);
        exit();
    }
