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
$string['filtername'] = 'Substitute';
$string['description'] = 'Replaces text with different text';

$string['find'] = 'Text to find';
$string['find_help'] = 'Search for this text. Suggested you use something like [[my-label]].';
$string['replace'] = 'Replace with';
$string['replace_help'] = 'When text is encountered, this is the value it will be replaced with.';
$string['builtins'] = 'Common substitutions';
$string['builtinsdesc'] = "The following identifiers can be injected into the HTML and will be replaced with their counterpart values:
    <code>%%PAGE:CONTEXTID%%</code>, <code>%%PAGE:CMID%%</code>, <code>%%PAGE:MODULE%%</code>, <code>%%COURSE:ID%%</code>, <code>%%COURSE:FULLNAME%%</code>, <code>%%COURSE:SHORTNAME%%</code>, <code>%%COURSE:IDNUMBER%%</code>, <code>%%USER:ID%%</code>, <code>%%USER:FIRSTNAME%%</code>, <code>%%USER:LASTNAME%%</code>, <code>%%USER:EMAIL%%</code>, <code>%%USER:USERNAME%%</code>, <code>%%USER:INSTITUTION%%</code>, <code>%%USER:DEPARTMENT%%</code>, <code>%%SESSION:KEY%%</code><br>
    The special <code>%%PREF:some-name%%</code> will look up the user preference for the current user and return the preference value matching <code>some-name</code> (or empty if not found).
";

$string['privacy:metadata'] = 'The Substitute filter does not store any personal data.';