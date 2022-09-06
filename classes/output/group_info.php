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
 * Group info page header
 *
 * Very similar to group details
 *
 * @package   theme_imtpn
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_imtpn\output;

use coding_exception;
use dml_exception;
use moodle_exception;
use renderable;
use renderer_base;
use stdClass;
use templatable;
use context_course;
use moodle_url;

/**
 * Group details page class.
 *
 *  * Very similar to group details
 *
 * @package   theme_imtpn
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_info implements renderable, templatable {

    /** @var stdClass $group An object with the group information. */
    protected $group;

    /**
     * @var $forum
     */
    protected $forum;

    /**
     * group_details constructor.
     *
     * @param int $groupid Group ID to show details of.
     * @param object $forum the forum
     * @throws dml_exception
     */
    public function __construct($groupid, $forum) {
        $this->group = groups_get_group($groupid, '*', MUST_EXIST);
        $this->forum = $forum;
    }

    /**
     * Export the data.
     *
     * @param renderer_base $output
     * @return stdClass
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function export_for_template(renderer_base $output) {
        global $USER;
        $data = new stdClass();
        if (!empty($this->group->description) || (!empty($this->group->picture) && empty($this->group->hidepicture))) {
            $context = context_course::instance($this->group->courseid);
            $data = new stdClass();
            $data->name = self::get_group_name($this->group);
            $data->pictureurl = static::get_group_picture_url($this->group, $this->group->courseid, true);
            $data->description = static::get_group_description($this->group);
            if (has_capability('moodle/course:managegroups', $context)) {
                $url = new moodle_url('/group/group.php', ['id' => $this->group->id, 'courseid' => $this->group->courseid]);
                $data->editurl = $url->out(false);
            }
            $currentgroupid = $this->group->id;
            $alldiscussions = mod_forum_get_discussion_summaries($this->forum, $USER, $currentgroupid, 0);

            $alldiscussions = array_filter($alldiscussions, function($disc) use ($currentgroupid) {
                return $disc->get_discussion()->get_group_id() == $currentgroupid;
            });
            $data->discussioncount = count($alldiscussions);
        }
        return $data;
    }

    /**
     * Get group name
     *
     * @param object $group
     * @return string
     */
    public static function get_group_name($group) {
        $context = context_course::instance($group->courseid);
        return format_string($group->name, true, ['context' => $context]);
    }

    /**
     * Override of the homonymous function in weblib.
     *
     * Here we want to display picture even if the user cannot manage groups.
     *
     * @param object $group
     * @param int $courseid
     * @param false $large
     * @param false $includetoken
     * @return moodle_url|void
     */
    public static function get_group_picture_url($group, $courseid, $large = false, $includetoken = false) {
        $context = context_course::instance($courseid);

        // If there is no picture, do nothing.
        if (!$group->picture) {
            return;
        }

        // If picture is hidden, only show to those with course:managegroups.
        if ($group->hidepicture) {
            return;
        }

        if ($large) {
            $file = 'f1';
        } else {
            $file = 'f2';
        }

        $grouppictureurl = moodle_url::make_pluginfile_url(
            $context->id, 'theme_imtpn', 'groupicon', $group->id, '/', $file, false, $includetoken);
        $grouppictureurl->param('rev', $group->picture);
        return $grouppictureurl;
    }

    /**
     * Get group description
     *
     * Patch to the usual procedure in order to retrieve the files without the need
     * to grant access to specific users.
     *
     * @param object $group
     * @return string
     */
    public static function get_group_description($group) {
        $context = context_course::instance($group->courseid);
        $description = file_rewrite_pluginfile_urls($group->description,
            'pluginfile.php',
            $context->id,
            'theme_imtpn',
            'groupdescription',
            $group->id);

        $descriptionformat = $group->descriptionformat ?? FORMAT_MOODLE;
        $options = [
            'overflowdiv' => true,
            'context' => $context
        ];
        return format_text($description, $descriptionformat, $options);
    }

}
