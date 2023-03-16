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
 * Handles fail or cancel requests of shurjopay paygw.
 *
 * @package    paygw_shurjopay
 * @copyright  2022 Brain station 23 ltd.
 * @author     Brain station 23 ltd.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_login();

$component = optional_param('component', PARAM_ALPHANUMEXT, PARAM_TEXT);
$paymentarea = optional_param('paymentarea', PARAM_ALPHANUMEXT, PARAM_TEXT);
$itemid = optional_param('itemid', 0,PARAM_INT);

if ($component == null || $paymentarea == null || $itemid == null) {
    $url = new moodle_url('/course');
} else {
    // Find redirection.
    $url = new moodle_url('/');

    // Method only exists in 3.11+.
    if (method_exists('\core_payment\helper', 'get_success_url')) {
        $url = helper::get_success_url($component, $paymentarea, $itemid);
    } else if ($component == 'enrol_fee' && $paymentarea == 'fee') {
        $courseid = $DB->get_field('enrol', 'courseid', ['enrol' => 'fee', 'id' => $itemid]);
        if (!empty($courseid)) {
            $url = course_get_url($courseid);
        }
    }
}

redirect($url, get_string('paymentfailed', 'paygw_shurjopay'), null, \core\output\notification::NOTIFY_ERROR);



