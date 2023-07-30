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
     * Add body additional classes when user is guest
     *
     * @param \moodle_page $page
     * @return void
     */
    public static function add_body_additional_classes(\moodle_page $page) {
        $loggedin = isloggedin() && !isguestuser();
        if (!$loggedin) {
            $page->add_body_class('notloggedin');
        }
    }
}
