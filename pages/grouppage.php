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

require_once('../../../config.php');

$groupid   = optional_param('groupid', 0, PARAM_INT);

global $DB, $USER;
$group = groups_get_group($groupid);
if (empty($group)) {
    print_error('invalid');
}

$course = $DB->get_record('course', array('id' => $group->courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
global $PAGE, $OUTPUT;

$PAGE->set_url(new moodle_url('/theme/imtpn/pages/grouppage.php', array('groupid'=> $groupid)));
$PAGE->set_title("$course->shortname: ".get_string('groups'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_docs_path('enrol/users');
$PAGE->add_body_class('path-user');                     // So we can style it independently.
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('murpedagogique', 'theme_imtpn'),
    new moodle_url('/theme/imtpn/pages/murpedagogique.php'));
$PAGE->navbar->add(get_string('groups'), new moodle_url('/theme/imtpn/pages/groupoverview.php', array('id'=>$course->id)));


$PAGE->set_other_editing_capability('moodle/course:manageactivities');
$mygroups = groups_get_user_groups($course->id);
$isingroup = false;
foreach($mygroups as $mygroup) {
    if ($mygroup[0] == $groupid) {
        $isingroup = true;
    }
}

if (!$isingroup && has_capability('theme/imtpn:canselfjoingroup', $context)) {
    $joingroup = $OUTPUT->single_button(
        new moodle_url('/theme/imtpn/pages/joingroup.php', array('groupid'=>$groupid)),
        get_string('joingroup', 'theme_imtpn'));
    $PAGE->set_button($joingroup);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($group->name);


// Display single group information if requested in the URL.
if ($groupid) {
    $grouprenderer = $PAGE->get_renderer('core_group');
    $groupdetailpage = new \core_group\output\group_details($groupid);
    echo $grouprenderer->group_details($groupdetailpage);
    $rulesgroups = get_config('theme_imtpn', 'murpedagogrouprules');
    echo $OUTPUT->box($rulesgroups);
}
echo $OUTPUT->footer();