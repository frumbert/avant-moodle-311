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
 * Provides local_avantws\external\api class.
 *
 * @package     local_avantws
 * @copyright   2023 tim.stclair@gmail.com https://www.frumbert.org/
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(
    'avantws_courseinfo' => array(
        'classname'     => 'local_avantws\external\api',
        'methodname'    => 'avantws_courseinfo',
        'description'   => 'Get courses in catalogue',
        'type'          => 'read',
        'ajax'          => true,
    ),
);
