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
 * Theme plugin version definition.
 *
 * @package   theme_imtpn
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use theme_imtpn\local\utils;

/**
 * Serves any files associated with the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 * @throws coding_exception
 */
function theme_imtpn_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    // Patch for groups so any user can see the icon + description.
    if (($filearea == 'groupicon' || $filearea == 'groupdescription') && $context->contextlevel == CONTEXT_COURSE) {
        global $DB;
        $fs = get_file_storage();

        require_course_login($course, true, null, false);

        $groupid = (int) array_shift($args);

        $group = $DB->get_record('groups', array('id' => $groupid, 'courseid' => $course->id), '*', MUST_EXIST);

        if ($filearea === 'groupdescription') {

            require_login($course);

            $filename = array_pop($args);
            $filepath = $args ? '/' . implode('/', $args) . '/' : '/';
            $file = $fs->get_file($context->id, 'group', 'description', $group->id, $filepath, $filename);
            if (!$file || $file->is_directory()) {
                send_file_not_found();
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            send_stored_file($file, 60 * 60, 0, $forcedownload, $options);

        } else if ($filearea === 'groupicon') {
            $filename = array_pop($args);

            if ($filename !== 'f1' && $filename !== 'f2') {
                send_file_not_found();
            }
            if (!$file = $fs->get_file($context->id, 'group', 'icon', $group->id, '/', $filename . '.png')) {
                if (!$file = $fs->get_file($context->id, 'group', 'icon', $group->id, '/', $filename . '.jpg')) {
                    send_file_not_found();
                }
            }

            \core\session\manager::write_close(); // Unlock session during file serving.
            send_stored_file($file, 60 * 60, 0, false, $options);

        } else {
            send_file_not_found();
        }
    }
    return theme_clboost\local\utils::generic_pluginfile('imtpn', $course, $cm, $context, $filearea, $args, $forcedownload,
        $options);
}

/**
 * Inject additional SCSS.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_imtpn_get_extra_scss($theme) {
    $extracss = theme_clboost_get_extra_scss($theme);
    $additionalcss = \theme_imtpn\profile::inject_scss($theme);
    return $extracss . $additionalcss;
}

/**
 * Setup customscript variable
 *
 * @throws coding_exception
 * @throws dml_exception
 */
function setup_customscripts() {
    \theme_imtpn\setup::setup_customscripts();
}
