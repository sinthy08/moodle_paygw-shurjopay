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
 * Various helper methods for interacting with the shurjopay API
 *
 * @package    paygw_shurjopay
 * @copyright  2022 Brain station 23 ltd.
 * @author     Brain station 23 ltd.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_shurjopay;

use stdClass;

/**
 * The helper class for shurjopay payment gateway.
 *
 * @copyright  2021 Brain station 23 ltd.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class shurjopay_helper {

    /**
     * @var string public business store ID
     */
    private $username;

    /**
     * @var string public business store password
     */
    private $password;

    /**
     * @var string public production environment
     */
    private $paymentmodes;

    /**
     * Initialise the shurjopay API client.
     *
     * @param string $username       the business store id
     * @param string $password business store password
     * @param bool   $paymentmodes         whether we are working with the sandbox environment or not
     */
    public function __construct(
        string $username,
        string $password,
        string $paymentmodes
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->paymentmodes = $paymentmodes;
        if($paymentmodes == 'sandbox') {
            $this->apiurl = 'https://sandbox.shurjopayment.com/api/get_token';
        } else {
            $this->apiurl = 'https://engine.shurjopayment.com/api/get_token';
        }
    }

    /**
     * Create a payment intent and return with the checkout session id.
     *
     * @return string
     *
     * @throws ApiErrorException
     */
    public function generate_payment(
        array $result,
        string $currency,
        float $cost,
        string $component,
        string $paymentarea,
        string $itemid
    ): void {
        global $CFG, $USER;

        $token = $result['token'];
        $cusname = $USER->firstname . ' ' . $USER->lastname;
        $cusemail = $USER->email;
        $cuscity = $USER->city;
        $cusaddress = $USER->address;
        $cusphone = $USER->phone1;

        $client_ip = $_SERVER['REMOTE_ADDR'];

        $order_id = 'sp_'.$component.'_'.$itemid;

        $postdata = [];
        $postdata['prefix'] = 'sp';
        $postdata['token'] = $token;
        $postdata['store_id'] = $result['store_id'];
        $postdata['currency'] = $currency;

        $postdata['value1'] = $component;
        $postdata['value2'] = $currency;
        $postdata['value3'] = $paymentarea;
        $postdata['value4'] = $itemid;

        $postdata['return_url'] = $CFG->wwwroot . '/payment/gateway/shurjopay/ipn.php';
        $postdata['cancel_url'] = $CFG->wwwroot . '/payment/gateway/shurjopay/cancel.php' ;

        // CUSTOMER INFORMATION.
        $postdata['customer_name'] = $cusname;
        $postdata['customer_phone'] = $cusphone;
        $postdata['customer_email'] = $cusemail;
        $postdata['customer_address'] = $cusaddress;
        $postdata['customer_city'] = $cuscity;
        $postdata['client_ip'] = $client_ip;

        //  REQUEST SEND TO shurjopay.
        if ($this->paymentmodes == 'live') {
            $localpc = true;
            $directapiurl = 'https://engine.shurjopayment.com/api/secret-pay?amount='.$cost.'&order_id='.$order_id;
        } else {
            $localpc = false;
            $directapiurl = 'https://sandbox.shurjopayment.com/api/secret-pay?amount='.$cost.'&order_id='.$order_id;
        }

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $directapiurl);   // The URL to fetch.
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, $localpc); // KEEP IT FALSE IF YOU RUN FROM LOCAL PC.

        $content = curl_exec($handle);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if ($code == 200 && !(curl_errno($handle))) {
            curl_close($handle);
            $shurjopayresponse = $content;
        } else {
            curl_close($handle);
            echo 'FAILED TO CONNECT WITH shurjopay API';
            exit;
        }

        // PARSE THE JSON RESPONSE.
        $checkout_data = json_decode($shurjopayresponse, true);


        if (isset($checkout_data['checkout_url']) && $checkout_data['checkout_url'] != '') {
            // THERE ARE MANY WAYS TO REDIRECT - Javascript, Meta Tag or Php Header Redirect or Other.
            echo "<meta http-equiv='refresh' content='0;url=" . $checkout_data['checkout_url']."'>";
            exit;
        } else {
            echo 'JSON Data parsing error!';
        }
    }

    public function generate_token () {
        global $CFG, $USER;

        $postdata = [];
        $postdata['username'] = $this->username;
        $postdata['password'] = $this->password;

        // REQUEST SEND TO shurjopay.
        $directapiurl = $this->apiurl;
        if ($this->paymentmodes == 'live') {
            $localpc = true;
        } else {
            $localpc = false;
        }

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $directapiurl);   // The URL to fetch.
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, $localpc); // KEEP IT FALSE IF YOU RUN FROM LOCAL PC.

        $content = curl_exec($handle);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if ($code == 200 && !(curl_errno($handle))) {
            curl_close($handle);
            $shurjopayresponse = $content;
        } else {
            curl_close($handle);
            echo 'FAILED TO CONNECT WITH shurjopay API';
            exit;
        }

        // PARSE THE JSON RESPONSE.
        $result = json_decode($shurjopayresponse, true);
        return $result;
    }

    public function varify_payment (string $token, string $order_id) {
        global $CFG, $USER;

        $postdata = [];
        $postdata['token'] = $token;
        $postdata['order_id'] = $order_id;

        // REQUEST SEND TO shurjopay.
        if ($this->paymentmodes == 'live') {
            $localpc = true;
            $directapiurl = 'https://engine.shurjopayment.com/api/verification';
        } else {
            $localpc = false;
            $directapiurl = 'https://sandbox.shurjopayment.com/api/verification';
        }

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $directapiurl);   // The URL to fetch.
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, $localpc); // KEEP IT FALSE IF YOU RUN FROM LOCAL PC.

        $content = curl_exec($handle);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($code == 200 && !(curl_errno($handle))) {
            curl_close($handle);
            $shurjopayresponse = $content;
        } else {
            curl_close($handle);
            echo 'FAILED TO CONNECT WITH shurjopay API';
            exit;
        }
        // PARSE THE JSON RESPONSE.
        $result = json_decode($shurjopayresponse, true);
        return $result;
    }
}
