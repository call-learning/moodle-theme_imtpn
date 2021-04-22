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

use theme_imtpn\mur_pedagogique;
use theme_imtpn\output\group_info;

require_once('../../../../config.php');

$groupid = required_param('groupid', PARAM_INT);
$sortorder = optional_param('o', null, PARAM_INT);
$pageno = optional_param('page', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);
$pageno = optional_param('p', $pageno, PARAM_INT);
$mode = optional_param('mode', 0, PARAM_INT);
$pagesize = optional_param('s', 0, PARAM_INT);

global $DB, $USER;
$group = groups_get_group($groupid);

$course = $DB->get_record('course', array('id' => $group->courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

$cm = \theme_imtpn\mur_pedagogique::get_cm();
if (!$cm) {
    print_error('cmshouldbedefined', 'theme_imtpn');
}
require_course_login($course, true, $cm);
global $PAGE, $OUTPUT;
$currenturl = new moodle_url('/theme/imtpn/pages/murpedagogique/grouppage.php', array('groupid' => $groupid));
$title =
    $PAGE->set_url($currenturl);
$PAGE->set_title("$course->shortname: " . get_string('groups'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('group-page');
$PAGE->set_subpage($groupid);
$PAGE->add_body_class('path-user');// So we can style it independently.
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('murpedagogique', 'theme_imtpn'),
    new moodle_url('/theme/imtpn/pages/murpedagogique/index.php'));
$PAGE->navbar->add(get_string('allgroups', 'theme_imtpn'),
    new moodle_url('/theme/imtpn/pages/murpedagogique/groupoverview.php', array('id' => $course->id)));
$PAGE->navbar->add($group->name, $currenturl);
$PAGE->add_body_class('path-mod-forum'); // Make sure the usual classes apply.
$PAGE->set_other_editing_capability('moodle/course:manageactivities');
$mygroups = groups_get_user_groups($course->id);
$isingroup = false;
foreach ($mygroups as $mygroupings) {
    foreach($mygroupings as $mygroup) {
        if ($mygroup == $groupid) {
            $isingroup = true;
            break;
        }
    }
}

if (!$isingroup && has_capability('theme/imtpn:canselfjoingroup', $context)) {
    $joingroup = $OUTPUT->single_button(
        new moodle_url('/theme/imtpn/pages/murpedagogique/joingroup.php', array('groupid' => $groupid)),
        get_string('joingroup', 'theme_imtpn'));
    $PAGE->set_button($joingroup);
}

$vaultfactory = \mod_forum\local\container::get_vault_factory();
$forumvault = $vaultfactory->get_forum_vault();
$forum = $forumvault->get_from_course_module_id($cm->id);

echo $OUTPUT->header();
echo $OUTPUT->heading($group->name);

// Display single group information if requested in the URL.
$grouprenderer = $PAGE->get_renderer('core_group');
$groupinfo = new group_info($groupid, $forum);

echo $OUTPUT->render($groupinfo);


if (!$isingroup) {
    $rulesgroups = get_config('theme_imtpn', 'murpedagogrouprules');
    echo $OUTPUT->box($rulesgroups);
} else {
    mur_pedagogique::display_posts($forum,
        $groupid,
        $mode,
        $search,
        $sortorder,
        $pageno,
        $pagesize);
}

echo $OUTPUT->footer();