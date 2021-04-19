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

defined('MOODLE_INTERNAL') || die();

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
    $additionalcss = \theme_imtpn\profile::inject_scss($theme->name);
    return $extracss . $additionalcss;
}

function reset_mur_pedago_blocks() {
    \theme_imtpn\setup::setup_murpedago_blocks();
}

/**
 * Fix issue with notloggedin class
 *
 * Usually the pages are marked as notlogged in if no user is logged in. In case the guest user
 * is logged in, the notloggedin is not there anymore, resulting in the left navbar taking space.
 * This resolves this issue on this theme.
 *
 * @param $page
 */
function theme_imtpn_page_init($page) {
    utils::set_additional_page_classes($page);
}