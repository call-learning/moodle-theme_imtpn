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
 * Utils
 *
 * @package   theme_imtpn
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_imtpn\local;

use coding_exception;
use context_system;
use dml_exception;
use html_writer;
use moodle_url;
use theme_imtpn\mur_pedagogique;

defined('MOODLE_INTERNAL') || die;

/**
 * Utils
 *
 * @package   theme_imtpn
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * Profile image file area name
     */
    const PROFILE_IMAGE_FILE_AREA = 'profileimage';

    /**
     * Get frontpage images URL
     *
     * @param string $themename
     * @return moodle_url[]
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function get_profile_page_image_url($themename) {
        $fs = get_file_storage();
        $syscontextid = context_system::instance()->id;
        $allfiles = $fs->get_area_files($syscontextid,
            'theme_' . $themename,
            self::PROFILE_IMAGE_FILE_AREA);

        $filesurl = [];
        foreach ($allfiles as $file) {
            if ($file->is_valid_image()) {
                $filename = $file->get_filename();
                $islg = substr($filename, -strlen(self::IMAGE_SIZE_TYPE_LG)) == self::IMAGE_SIZE_TYPE_LG;
                $isxl = substr($filename, -strlen(self::IMAGE_SIZE_TYPE_XL)) == self::IMAGE_SIZE_TYPE_XL;
                $type = $islg ? self::IMAGE_SIZE_TYPE_LG : self::IMAGE_SIZE_TYPE_NORMAL;
                $type = $isxl ? self::IMAGE_SIZE_TYPE_XL : $type;

                $filesurl[$type] = moodle_url::make_pluginfile_url(
                    $syscontextid,
                    'theme_' . $themename,
                    self::PROFILE_IMAGE_FILE_AREA,
                    0,
                    $file->get_filepath(),
                    $filename
                )->out_as_local_url();
            }
        }
        if (count($filesurl)) {
            if (!isset($filesurl[self::IMAGE_SIZE_TYPE_LG])) {
                $filesurl[self::IMAGE_SIZE_TYPE_LG] = $filesurl[self::IMAGE_SIZE_TYPE_NORMAL];
            }
            if (!isset($filesurl[self::IMAGE_SIZE_TYPE_XL])) {
                $filesurl[self::IMAGE_SIZE_TYPE_XL] = $filesurl[self::IMAGE_SIZE_TYPE_NORMAL];
            }
        }
        return $filesurl;
    }

    /**
     * Normal Size for image
     */
    const IMAGE_SIZE_TYPE_NORMAL = 'normal';
    /**
     * LG Size for image
     */
    const IMAGE_SIZE_TYPE_LG = 'lg';
    /**
     * XL Size for image
     */
    const IMAGE_SIZE_TYPE_XL = 'xl';

    /**
     * Set additional CSS to page
     *
     * @param \moodle_page $page
     * @throws coding_exception
     */
    public static function set_additional_page_classes(&$page) {
        $loggedin = isloggedin() && !isguestuser();
        if (!$loggedin) {
            $page->add_body_class('notloggedin');
        }
        try {
            $murpedaggocm = mur_pedagogique::get_cm();
            $murpedaggocontext = \context_module::instance($murpedaggocm->id);
        } catch(\moodle_exception $e) {
            $murpedaggocm = null;
            $murpedaggocontext = null;
        }
        $isonmurpedago = !empty($murpedaggocm) && !empty($page->cm->context) &&
                $page->cm->context->is_child_of($murpedaggocontext, true);
        if ($isonmurpedago ) {
            $page->add_body_class('mur-pedagogique'); // We make sure a generic class is set to
            // apply custom CSS.
            $page->add_body_class('path-mod-forum'); // Make sure the usual classes apply.
            // Also if child context, we need to make sure we adjust the breadcrumb.
            if ($page->cm->context->is_child_of($murpedaggocontext, true)) {
                $page->navbar->ignore_active();
            }
        }
    }
}