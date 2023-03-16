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
 * shurjopay enrolments plugin settings and presets.
 *
 * @package    paygw_shurjopay
 * @copyright  2022 Brain station 23 ltd.
 * @author     Brain station 23 ltd.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_shurjopay\shurjopay_helper;

require_once("../../../config.php");
require_login();

global $DB, $USER, $OUTPUT, $CFG, $PAGE;

require_once($CFG->dirroot . '/course/lib.php');


$url = new moodle_url('/course');
redirect($url, get_string('paymentcancelled', 'paygw_shurjopay'), 0, 'failed');
