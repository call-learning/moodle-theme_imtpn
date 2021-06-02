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
 * Print a group page description
 *
 * @package   theme_imtpn
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
global $DB, $USER, $PAGE, $OUTPUT, $CFG;

require_once($CFG->dirroot . '/group/lib.php');
$groupid = required_param('groupid', PARAM_INT);
$group = groups_get_group($groupid);
if (empty($group)) {
    print_error('invalid');
}

$course = $DB->get_record('course', array('id' => $group->courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);
require_login($course->id);
if (!has_capability('theme/imtpn:canselfjoingroup', $context)) {
    print_error('selfjoinerror');
}
$PAGE->set_url(new moodle_url('/theme/imtpn/pages/murpedagogique/joingroup.php', array('groupid' => $groupid)));
$PAGE->set_title("$course->shortname: " . get_string('groups'));
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('groups'),
    new moodle_url('/theme/imtpn/pages/murpedagogique/groupoverview.php', array('id' => $course->id)),
    navbar::TYPE_CUSTOM, null, 'allgroups'
);

echo $OUTPUT->header();
if (groups_add_member($groupid, $USER->id, 'theme_imtpn')) {
    echo $OUTPUT->notification(get_string('groupjoined', 'theme_imtpn', $group->name), 'notifysuccess');
} else {
    echo $OUTPUT->notification(get_string('cannotjoin', 'theme_imtpn', $group->name), 'notifyfailure');
}
// Display single group information if requested in the URL.
echo $OUTPUT->single_button(
    new moodle_url('/theme/imtpn/pages/murpedagogique/grouppage.php', array('groupid' => $group->id)),
    get_string('continue'));
echo $OUTPUT->footer();
