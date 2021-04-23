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

use core_table\local\filter\filter;
use core_table\local\filter\integer_filter;
use core_table\local\filter\string_filter;
use theme_imtpn\local\utils;
use theme_imtpn\mur_pedagogique;

require_once('../../../../config.php');
global $CFG, $PAGE, $DB, $OUTPUT;
require_once($CFG->libdir . '/filelib.php');

define('OVERVIEW_NO_GROUP', -1); // The fake group for users not in a group.
define('OVERVIEW_GROUPING_GROUP_NO_GROUPING', -1); // The fake grouping for groups that have no grouping.
define('OVERVIEW_GROUPING_NO_GROUP', -2); // The fake grouping for users with no group.

$courseid = optional_param('id', 0, PARAM_INT);

$cm = mur_pedagogique::get_cm();
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

navigation_node::override_active_url($currenturl);
$PAGE->navbar->add(get_string('overview', 'group'));

// Print header.
$PAGE->set_title($strgroups);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('standard');
$PAGE->set_pagetype('group-overview');
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('murpedagogique', 'theme_imtpn'),
    new moodle_url('/theme/imtpn/pages/murpedagogique/index.php'));
$PAGE->navbar->add(get_string('allgroups', 'theme_imtpn'),
    $currenturl);

// The form.
require_once($CFG->libdir.'/formslib.php');

$form = new class() extends moodleform {
    public function __construct($action=null, $customdata=null, $method='post', $target='', $attributes=null, $editable=true,
        $ajaxformdata=null) {
        parent::__construct($action, $customdata, $method, $target,['class'=>'groupoverview-search-form container d-flex'] , $editable,$ajaxformdata);
    }
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement(
            'text', 'groupname', get_string('groupname', 'theme_imtpn'),
            ['class'=>'container']
        );
        $mform->setType('groupname', PARAM_TEXT);

        $mform->addElement('submit', 'submitbutton', get_string('search'));
        $mform->addElement('cancel', 'cancelbutton', get_string('clear'),
        ['class'=>'mr-auto']);
    }
};
$groupname = '';
if ($form->is_cancelled()) {
    $groupname = "";
} else {
    if ($data = $form->get_data()) {
        $groupname = $data->groupname;
    }
}
$filterset = new \theme_imtpn\table\groups_filterset();
$filterset->add_filter(new integer_filter('courseid', filter::JOINTYPE_DEFAULT, [(int)$course->id]));
if (!empty($groupname)) {
    $filterset->add_filter(new string_filter('name', filter::JOINTYPE_DEFAULT, [$groupname]));
}


/* @var core_renderer $OUTPUT */
echo $OUTPUT->header();
echo $OUTPUT->box_start('py-5');
$form->display();
echo $OUTPUT->box_end();
// Print overview.
echo $OUTPUT->heading(format_string($course->shortname, true, array('context' => $context)) . ' ' . $stroverview, 3);
$grouptable = new \theme_imtpn\table\groups(html_writer::random_id());
$grouptable->set_filterset($filterset);
$grouptable->out(20, true);
echo $OUTPUT->footer();

