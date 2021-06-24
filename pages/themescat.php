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
 * Editable theme catalogue page
 *
 * Provide an editable page
 *
 * @package   theme_imtpn
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
global $SITE, $CFG, $DB, $PAGE, $USER, $OUTPUT;

$pageid = optional_param('id', null, PARAM_INT);
$pageidnumber = optional_param('p', null, PARAM_ALPHANUMEXT);
$edit = optional_param('edit', null, PARAM_BOOL);    // Turn editing on and off.

require_login();

$context = context_system::instance();

$pagetitle = get_string('themescat:title', 'theme_imtpn');
$header = "$SITE->shortname: $pagetitle";
$PAGE->set_blocks_editing_capability('theme/imtpn:editcataloguethemes');

// Start setting up the page.
$baseurl = new moodle_url('/theme/imtpn/pages/themescat.php');
$PAGE->set_context($context);
$PAGE->set_url($baseurl);
$PAGE->set_pagelayout("standard");
$PAGE->set_pagetype('themescat');
$PAGE->blocks->add_region('content');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($header);
$PAGE->set_subpage($pageid);

// Toggle the editing state and switches.
$editbutton = "";
if ($PAGE->user_allowed_editing()) {
    if ($edit !== null) {             // Editing state was specified.
        $USER->editing = $edit;       // Change editing state.
    }
    if (!empty($USER->editing)) {
        $edit = 1;
    } else {
        $edit = 0;
    }
} else {
    $USER->editing = $edit = 0;
}
$currentbuttons = $PAGE->button;

$url = new moodle_url("$CFG->wwwroot/local/resourcelibrary/index.php");
$button = new single_button($url, get_string('viewcatalog', 'theme_imtpn'), 'post', true);
$currentbuttons .= $OUTPUT->render($button);

// Now check if user can either create a new course.
$defaultcategoryid = core_course_category::get_default()->id;
$category = $DB->get_record('course_categories', array('id' => $defaultcategoryid), '*');
$cancreate = isloggedin();
if ($category) {
    $catcontext = context_coursecat::instance($category->id);
    $cancreate = $cancreate && has_capability('moodle/course:create', $catcontext);
    if ($cancreate) {
        $createurl = new moodle_url("$CFG->wwwroot/course/edit.php", array('category' =>
            $category->id, 'returnto' => $baseurl->out()));
        $button = new single_button($createurl, get_string('createcourse', 'theme_imtpn'), 'post', true);
        $currentbuttons .= $OUTPUT->render($button);
    }
}
if (has_capability('theme/imtpn:editcataloguethemes', $context)) {
    $currentbuttons .= $OUTPUT->edit_button($baseurl);
}
$PAGE->set_button($currentbuttons);

$PAGE->blocks->set_default_region('content');

echo $OUTPUT->header();

echo $OUTPUT->custom_block_region('content');

echo $OUTPUT->footer();
