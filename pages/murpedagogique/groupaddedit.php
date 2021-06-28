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
 * Create group OR edit group settings.
 *
 * From core_group
 *
 * @package   theme_imtpn
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
global $CFG, $PAGE, $DB, $OUTPUT;
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/group/group_form.php');

// Get url variables.
$courseid = optional_param('courseid', 0, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

if ($id) {
    if (!$group = $DB->get_record('groups', array('id' => $id))) {
        throw new moodle_exception('invalidgroupid');
    }
    if (empty($courseid)) {
        $courseid = $group->courseid;

    } else if ($courseid != $group->courseid) {
        throw new moodle_exception('invalidcourseid');
    }

    if (!$course = $DB->get_record('course', array('id' => $courseid))) {
        throw new moodle_exception('invalidcourseid');
    }

} else {
    if (!$course = $DB->get_record('course', array('id' => $courseid))) {
        throw new moodle_exception('invalidcourseid');
    }
    $group = new stdClass();
    $group->courseid = $course->id;
}

if ($id !== 0) {
    $PAGE->set_url('/theme/imtpn/pages/murpedagogique/groupaddedit.php', array('id' => $id));
} else {
    $PAGE->set_url('/theme/imtpn/pages/murpedagogique/groupaddedit.php', array('courseid' => $courseid));
}

require_login($course);
$context = context_course::instance($course->id);
require_capability('moodle/course:managegroups', $context);

$strgroups = get_string('groups');
$PAGE->set_title($strgroups);
$PAGE->set_heading($course->fullname . ': ' . $strgroups);
$PAGE->set_pagelayout('admin');
navigation_node::override_active_url(new moodle_url('/group/index.php', array('id' => $course->id)));

$returnurl = $CFG->wwwroot . '/theme/imtpn/pages/murpedagogique/groupoverview.php?id=' . $course->id . '&group=' . $id;

// Prepare the description editor: We do support files for group descriptions.
$editoroptions =
    array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $course->maxbytes, 'trust' => false, 'context' => $context,
        'noclean' => true);
if (!empty($group->id)) {
    $editoroptions['subdirs'] = file_area_contains_subdirs($context, 'group', 'description', $group->id);
    $group = file_prepare_standard_editor($group, 'description', $editoroptions, $context, 'group', 'description', $group->id);
} else {
    $editoroptions['subdirs'] = false;
    $group = file_prepare_standard_editor($group, 'description', $editoroptions, $context, 'group', 'description', null);
}

// First create the form.
$editform = new group_form(null, array('editoroptions' => $editoroptions));
$editform->set_data($group);

if ($editform->is_cancelled()) {
    redirect($returnurl);

} else if ($data = $editform->get_data()) {
    if (!has_capability('moodle/course:changeidnumber', $context)) {
        // Remove the idnumber if the user doesn't have permission to modify it.
        unset($data->idnumber);
    }

    if ($data->id) {
        groups_update_group($data, $editform, $editoroptions);
    } else {
        $id = groups_create_group($data, $editform, $editoroptions);
        $returnurl = new moodle_url('/theme/imtpn/pages/murpedagogique/grouppage.php', array('groupid' => $id));
    }

    redirect($returnurl);
}

$strgroups = get_string('groups');
$strparticipants = get_string('participants');

if ($id) {
    $strheading = get_string('editgroupsettings', 'group');
} else {
    $strheading = get_string('creategroup', 'group');
}

$PAGE->navbar->add($strgroups,
    new moodle_url('/theme/imtpn/pages/murpedagogique/groupoverview.php', array('id' => $courseid)),
    navbar::TYPE_CUSTOM, null, 'allgroups'
);
$PAGE->navbar->add($strheading);

// Print header.
echo $OUTPUT->header();
echo '<div id="grouppicture">';
if ($id) {
    print_group_picture($group, $course->id);
}
echo '</div>';
$editform->display();
echo $OUTPUT->footer();
