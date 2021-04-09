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
 * Print an overview of groupings & group membership
 *
 * @package   theme_imtpn
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
global $CFG, $PAGE, $DB, $OUTPUT;
require_once($CFG->libdir . '/filelib.php');

define('OVERVIEW_NO_GROUP', -1); // The fake group for users not in a group.
define('OVERVIEW_GROUPING_GROUP_NO_GROUPING', -1); // The fake grouping for groups that have no grouping.
define('OVERVIEW_GROUPING_NO_GROUP', -2); // The fake grouping for users with no group.

$courseid = optional_param('id', 0, PARAM_INT);

$cm = \theme_imtpn\mur_pedagogique::get_cm();
if ($cm) {
    $PAGE->set_cm($cm);
} else {
    print_error('invalidcourse');
}
if (empty($courseid)) {
    $courseid = $cm->course;
}

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse');
}

$currenturl = new moodle_url('/theme/imtpn/pages/murpedagogique/groupoverview.php', array('id' => $courseid));
$PAGE->set_url($currenturl);

// Make sure that the user has permissions to manage groups.
require_course_login($course, true, $cm);

$context = context_course::instance($courseid);
require_capability('moodle/site:accessallgroups', $context);

$strgroups = get_string('allgroups', 'theme_imtpn');
$strparticipants = get_string('participants');
$stroverview = get_string('overview', 'group');
$strgrouping = get_string('grouping', 'group');
$strgroup = get_string('group', 'group');
$strnotingrouping = get_string('notingrouping', 'group');
$strnogroups = get_string('nogroups', 'group');
$strdescription = get_string('description');

// This can show all users and all groups in a course.
// This is lots of data so allow this script more resources.
raise_memory_limit(MEMORY_EXTRA);

// Get all groupings and sort them by formatted name.
$groupings = $DB->get_records('groupings', array('courseid' => $courseid), 'name');
foreach ($groupings as $gid => $grouping) {
    $groupings[$gid]->formattedname = format_string($grouping->name, true, array('context' => $context));
}
core_collator::asort_objects_by_property($groupings, 'formattedname');
$members = array();
foreach ($groupings as $grouping) {
    $members[$grouping->id] = array();
}
// Groups not in a grouping.
$members[OVERVIEW_GROUPING_GROUP_NO_GROUPING] = array();

// Get all groups
$groups = $DB->get_records('groups', array('courseid' => $courseid), 'name');

$params = array('courseid' => $courseid);

list($sort, $sortparams) = users_order_by_sql('u');

$extrafields = get_extra_user_fields($context);
$extrafields[] = 'picture';
$extrafields[] = 'imagealt';
$allnames = 'u.id, ' . user_picture::fields('u', $extrafields);

$sql = "SELECT g.id AS groupid, gg.groupingid, u.id AS userid, $allnames, u.idnumber, u.username
          FROM {groups} g
               LEFT JOIN {groupings_groups} gg ON g.id = gg.groupid
               LEFT JOIN {groups_members} gm ON g.id = gm.groupid
               LEFT JOIN {user} u ON gm.userid = u.id
         WHERE g.courseid = :courseid 
      ORDER BY g.name, $sort";

$rs = $DB->get_recordset_sql($sql, array_merge($params, $sortparams));
foreach ($rs as $row) {
    $user = username_load_fields_from_object((object) [], $row, null,
        array_merge(['id' => 'userid', 'username', 'idnumber'], $extrafields));

    if (!$row->groupingid) {
        $row->groupingid = OVERVIEW_GROUPING_GROUP_NO_GROUPING;
    }
    if (!array_key_exists($row->groupid, $members[$row->groupingid])) {
        $members[$row->groupingid][$row->groupid] = array();
    }
    if (!empty($user->id)) {
        $members[$row->groupingid][$row->groupid][] = $user;
    }
}
$rs->close();

navigation_node::override_active_url($currenturl);
$PAGE->navbar->add(get_string('overview', 'group'));

/// Print header
$PAGE->set_title($strgroups);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('standard');
$PAGE->set_pagetype('group-overview');
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('murpedagogique', 'theme_imtpn'),
    new moodle_url('/theme/imtpn/pages/murpedagogique/index.php'));
$PAGE->navbar->add(get_string('allgroups', 'theme_imtpn'),
    $currenturl);
echo $OUTPUT->header();

/// Print overview
echo $OUTPUT->heading(format_string($course->shortname, true, array('context' => $context)) . ' ' . $stroverview, 3);

/// Print table
$printed = false;
$hoverevents = array();
foreach ($members as $gpgid => $groupdata) {
    $table = new html_table();
    $table->head = array(get_string('groupscount', 'group', count($groupdata)), get_string('groupmembers', 'group'),
        get_string('usercount', 'group'));
    $table->size = array('20%', '70%', '10%');
    $table->align = array('left', 'left', 'center');
    $table->width = '90%';
    $table->data = array();
    foreach ($groupdata as $gpid => $users) {
        $line = array();
        $pictureurl = get_group_picture_url($groups[$gpid], $courseid, false, false);
        $groupname = s($groups[$gpid]->name);
        $name = html_writer::link(
            new moodle_url('/theme/imtpn/pages/murpedagogique/grouppage.php', array('groupid' => $gpid)),
            html_writer::img($pictureurl, $groupname, ['title' => $groupname])
        );
        $description =
            file_rewrite_pluginfile_urls($groups[$gpid]->description, 'pluginfile.php', $context->id, 'group', 'description',
                $gpid);
        $options = new stdClass;
        $options->noclean = true;
        $options->overflowdiv = true;
        $jsdescription = trim(format_text($description, $groups[$gpid]->descriptionformat, $options));
        if (empty($jsdescription)) {
            $line[] = $name;
        } else {
            $line[] = html_writer::tag('span', $name, array('class' => 'group_hoverdescription', 'data-groupid' => $gpid));
            $hoverevents[$gpid] = get_string('descriptiona', null, $jsdescription);
        }
        $viewfullnames = has_capability('moodle/site:viewfullnames', $context);
        $fullnames = array();
        foreach ($users as $user) {
            /* @var $OUTPUT core_renderer */
            $fullnames[] = $OUTPUT->user_picture($user, ['includefullname' => true]);
        }
        $line[] = implode(', ', $fullnames);
        $line[] = count($users);
        $table->data[] = $line;
    }
    if ($gpgid > 0) {
        echo $OUTPUT->heading($groupings[$gpgid]->formattedname, 3);
        $description =
            file_rewrite_pluginfile_urls($groupings[$gpgid]->description, 'pluginfile.php', $context->id, 'grouping', 'description',
                $gpgid);
        $options = new stdClass;
        $options->overflowdiv = true;
        echo $OUTPUT->box(format_text($description, $groupings[$gpgid]->descriptionformat, $options),
            'generalbox boxwidthnarrow boxaligncenter');
    }
    echo html_writer::table($table);
    $printed = true;
}
echo $OUTPUT->footer();
