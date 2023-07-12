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
 * @package    filter
 * @subpackage substitute
 * @copyright  tim@avideelearning.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

	$settings->add(new admin_setting_description(
		'builtins',
		new lang_string('builtins', 'filter_substitute'),
		new lang_string('builtinsdesc', 'filter_substitute'),
	));

	// arbitarily support 16 substitutions
	for ( $i = 0 ; $i < 16; $i++ ) {
	    $item = new admin_setting_configtext('filter_substitute/find_' . $i,
                                        	new lang_string('find', 'filter_substitute'),
                                        	new lang_string('find_help', 'filter_substitute'),
                                        	'',
                                        	PARAM_RAW);
	    $settings->add($item);
    	$item = new admin_setting_confightmleditor('filter_substitute/replace_' . $i,
												new lang_string('replace', 'filter_substitute'),
                                                new lang_string('replace_help', 'filter_substitute'),
                                                '');
	    $settings->add($item);
   }

}
