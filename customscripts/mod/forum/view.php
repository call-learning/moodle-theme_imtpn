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

$managerfactory = mod_forum\local\container::get_manager_factory();
$legacydatamapperfactory = mod_forum\local\container::get_legacy_data_mapper_factory();
$vaultfactory = mod_forum\local\container::get_vault_factory();
$forumvault = $vaultfactory->get_forum_vault();
$discussionvault = $vaultfactory->get_discussion_vault();
$postvault = $vaultfactory->get_post_vault();
$discussionlistvault = $vaultfactory->get_discussions_in_forum_vault();

$cmid = optional_param('id', 0, PARAM_INT);
$forumid = optional_param('f', 0, PARAM_INT);
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
global $DB;
$murpedagoidnumber = get_config('theme_imtpn', 'murpedagoidnumber');

$murpedagogiquecm = $DB->get_record('course_modules', array('idnumber' => $murpedagoidnumber));

if (!empty($murpedagogiquecm) && ($murpedagogiquecm->id === $forum->get_course_module_record()->id)) {
    global $CFG;
    \theme_imtpn\mur_pedagogique::display_page($forum, $managerfactory, $legacydatamapperfactory, $discussionlistvault, $postvault, $mode,
        $search, $sortorder, $pageno, $pagesize);
    die();
}
