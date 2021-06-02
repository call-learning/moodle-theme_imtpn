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
 * Displays the list of discussions in a forum.
 *
 * @package   mod_forum
 * @copyright 2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_forum\grades\forum_gradeitem;

$vaultfactory = mod_forum\local\container::get_vault_factory();
$forumvault = $vaultfactory->get_forum_vault();

$cmid = optional_param('id', 0, PARAM_INT);
$forumid = optional_param('f', 0, PARAM_INT);
$groupid = optional_param('group', 0, PARAM_INT);
$mode = optional_param('mode', 0, PARAM_INT);
$showall = optional_param('showall', '', PARAM_INT);
$pageno = optional_param('page', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_CLEAN);
$pageno = optional_param('p', $pageno, PARAM_INT);
$pagesize = optional_param('s', 0, PARAM_INT);
$sortorder = optional_param('o', null, PARAM_INT);

if (!$cmid && !$forumid) {
    print_error('missingparameter');
}

if ($cmid) {
    $forum = $forumvault->get_from_course_module_id($cmid);
    if (empty($forum)) {
        throw new \moodle_exception('Unable to find forum with cmid ' . $cmid);
    }
} else {
    $forum = $forumvault->get_from_id($forumid);
    if (empty($forum)) {
        throw new \moodle_exception('Unable to find forum with id ' . $forumid);
    }
}
$cm = \theme_imtpn\mur_pedagogique::get_cm();
global $PAGE;
// Go to the mur pedagogique page if it is the right forum and user is not editing (if not
// it will go to the normal forum page)
if (!empty($cm) && ($cm->id === $forum->get_course_module_record()->id)) {
    global $CFG;
    $PAGE->set_cm($cm);

    if (!empty($groupid)) {
        redirect(new moodle_url('/theme/imtpn/pages/murpedagogique/grouppage.php', array('groupid' => $groupid)));
    }
    if (!$PAGE->user_is_editing()) {
        \theme_imtpn\mur_pedagogique::display_wall($forum,
            FORUM_MODE_NESTED,
            $search,
            $sortorder,
            $pageno,
            $pagesize);
        die();
    }
}

