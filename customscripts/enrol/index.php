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
 * This page shows all course enrolment options for current user.
 *
 * We modified it so it shows the syllabus instead.
 *
 * @package    theme_imtpn
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// phpcs:disable
// @codingStandardsIgnoreStart
$id = required_param('id', PARAM_INT);
$returnurl = optional_param('returnurl', 0, PARAM_LOCALURL);
if (!isloggedin()) {
    $referer = get_local_referer();
    if (empty($referer)) {
        // A user that is not logged in has arrived directly on this page,
        // they should be redirected to the course page they are trying to enrol on after logging in.
        $SESSION->wantsurl = "$CFG->wwwroot/course/view.php?id=$id";
    }
    // do not use require_login here because we are usually coming from it,
    // it would also mess up the SESSION->wantsurl
    redirect(get_login_url());
}

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

// Everybody is enrolled on the frontpage
if ($course->id == SITEID) {
    redirect("$CFG->wwwroot/");
}

if (!$course->visible && !has_capability('moodle/course:viewhiddencourses', context_course::instance($course->id))) {
    print_error('coursehidden');
}

$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/enrol/index.php', array('id'=>$course->id));

// do not allow enrols when in login-as session
if (\core\session\manager::is_loggedinas() and $USER->loginascontext->contextlevel == CONTEXT_COURSE) {
    print_error('loginasnoenrol', '', $CFG->wwwroot.'/course/view.php?id='.$USER->loginascontext->instanceid);
}

// Check if user has access to the category where the course is located.
if (!core_course_category::can_view_course_info($course) && !is_enrolled($context, $USER, '', true)) {
    print_error('coursehidden', '', $CFG->wwwroot . '/');
}

// Then redirect
if (!empty($CFG->enablesyllabus)) {
    redirect(new moodle_url('/local/syllabus/view.php', array('id'=>$course->id)));
}

