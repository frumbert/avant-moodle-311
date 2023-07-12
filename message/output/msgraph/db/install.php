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
 * Installation code for the msgraph message processor
 *
 * @package    message_msgraph
 * @copyright  2021 Daniel Neis Araujo <daniel@adapta.online>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Install the msgraph message processor
 */
function xmldb_message_msgraph_install() {
    global $DB;
    $result = true;

    $provider = new stdClass();
    $provider->name  = 'msgraph';
    $DB->insert_record('message_processors', $provider);
    return $result;
}
