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
 * Version details
 *
 * @package    filter
 * @subpackage substitute
 * @copyright  tim@avideelearning.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class filter_substitute extends moodle_text_filter {

    // currently support 16 replacements
    public function filter($text, array $options = array()) {

        // process user replacements
        for ( $i = 0 ; $i < 16; $i++ ) {
            $find = trim(get_config('filter_substitute', 'find_' . $i));
            if (!empty($find)) {
                $replace = trim(get_config('filter_substitute', 'replace_' . $i));
                if (!empty($replace)) {
                    $text = str_replace($find, $replace, $text);
                }
            }
        }
        // then internal known values
        $text = self::replace_internals($text);
 
        return $text;
    }

    // we have some hard coded replacments we can find
    // in the unlikely format %%AREA:VARIABLE%% (case-sensitive)
    private static function replace_internals($text) {
        global $USER, $COURSE, $PAGE;
        $cmid = @$PAGE->cm->id; // ignore if not set
        $modname = @$PAGE->cm->modname; // ignore if not set
        $find = array_map(function($n){return "%%{$n}%%";}, [
            'PAGE:CONTEXTID',
            'PAGE:CMID',
            'PAGE:MODULE',

            'COURSE:ID',
            'COURSE:FULLNAME',
            'COURSE:SHORTNAME',
            'COURSE:IDNUMBER',

            'USER:ID',
            'USER:FIRSTNAME',
            'USER:LASTNAME',
            'USER:EMAIL',
            'USER:USERNAME',
            'USER:INSTITUTION',
            'USER:DEPARTMENT',

            'SESSION:KEY'
        ]);
        $repl = [
            $PAGE->context->id,
            $cmid ?? 0,
            $modname ?? '',

            $COURSE->id,
            $COURSE->fullname,
            $COURSE->shortname,
            $COURSE->idnumber,

            $USER->id ?? 0,
            $USER->firstname ?? '',
            $USER->lastname ?? '',
            $USER->email ?? '',
            $USER->username ?? '',
            $USER->institution ?? '',
            $USER->department ?? '',

            sesskey()
        ];
        $text = str_replace($find, $repl, $text);

        if (preg_match('/%%PREF:([^%]*)%%/', $text)) {
            $prefs = [];
            preg_match_all('/%%PREF:([^%]*)%%/', $text, $prefs);
            foreach ($prefs[1] as $index => $pref) {
                $value = get_user_preferences($pref, '', $USER);
                $text = str_replace($prefs[0][$index], $value, $text);
            }
        }

        return $text;

        // return self::mb_str_replace($find, $repl, $text);
    }

    // private static function mb_str_replace($needle, $replacement, $haystack) {
    //     return implode($replacement, mb_split($needle, $haystack));
    // }
}