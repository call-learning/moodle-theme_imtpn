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
 * @param \core_user\output\myprofile\tree $tree
 * @param $user
 * @param $iscurrentuser
 * @param $course
 */
function theme_imtpn_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {

}

/**
 * Inject additional SCSS.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_imtpn_get_extra_scss($theme) {
    $extracss = theme_clboost_get_extra_scss($theme);

    $profileimageurl = utils::get_profile_page_image_url($theme->name);
    if (empty($profileimageurl)) {
        $profileimageurl[utils::IMAGE_SIZE_TYPE_NORMAL] = '[[pix:theme|backgrounds/profile]]';
        $profileimageurl[utils::IMAGE_SIZE_TYPE_LG] = '[[pix:theme|backgrounds/profile-2x]]';
        $profileimageurl[utils::IMAGE_SIZE_TYPE_XL] = '[[pix:theme|backgrounds/profile-3x]]';
    }
    $profileimagedef = '
    #page-user-profile {
        #page-header {
        ';
    foreach ($profileimageurl as $type => $def) {
        $bgdef = "
        background-size: cover;
        background-image: url($def);";
        if ($type != utils::IMAGE_SIZE_TYPE_NORMAL) {
            $profileimagedef .= " @include media-breakpoint-up($type) {
                $bgdef
             }";
        } else {
            $profileimagedef .= $bgdef;
        }

    }
    $profileimagedef .= '
        }
    }';
    return $extracss . $profileimagedef;
}

function reset_mur_pedago_blocks() {
    setup::setup_murpedago_blocks();
}