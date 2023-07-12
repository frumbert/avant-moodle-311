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
 * Provides local_avantws\external\st_apply_token trait.
 *
 * @package     local_avantws
 * @category    external
 * @copyright   2023 tim.stclair@gmail.com https://www.frumbert.org/
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_avantws\external;

defined('MOODLE_INTERNAL') || die();

use external_function_parameters;
use external_single_structure;
use external_value;

trait avantws_courseinfo {

    public static function avantws_courseinfo_parameters() {
        return new external_function_parameters(
            array(
                // no params
            )
        );
    }

    public static function avantws_courseinfo_returns() {
        return new external_single_structure(
            [
                'courses' => new external_value(PARAM_RAW, 'JSON data of the records.'),
                'hash' => new external_value(PARAM_RAW, 'md5 hash of courses payload')
            ]
        );
    }
    
    public static function avantws_courseinfo() {
    global $DB, $CFG;
        $results = [];
        $courses = $DB->get_records('course', ['visible' => 1]);
        foreach ($courses as $dbcourse) {
            if ($dbcourse->id == 1) continue;
            $course = new \core_course_list_element($dbcourse);
            $data = \local_aurora_observer::local_aurora_get_meta_from_course($course);
            if (isset($data['listed']) && $data['listed']!="0") {
                if (isset($data['image'])) {
                    $data['image'] = $data['image'][0];
                }
                $data['hash'] = md5(json_encode($data, JSON_NUMERIC_CHECK));
                $results[] = $data;
            }
        }

        $output = json_encode($results, JSON_NUMERIC_CHECK);
        return [
            "courses" => $output,
            "hash" => md5($output)
        ];

    }

}