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
 * @package   theme_imtpn
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../../config.php');
$managerfactory = mod_forum\local\container::get_manager_factory();
$legacydatamapperfactory = mod_forum\local\container::get_legacy_data_mapper_factory();
$vaultfactory = mod_forum\local\container::get_vault_factory();
$forumvault = $vaultfactory->get_forum_vault();
$discussionvault = $vaultfactory->get_discussion_vault();
$postvault = $vaultfactory->get_post_vault();
$discussionlistvault = $vaultfactory->get_discussions_in_forum_vault();

$forumid = optional_param('f', 0, PARAM_INT);
$pageno = optional_param('page', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_CLEAN);
$pageno = optional_param('p', $pageno, PARAM_INT);
$mode = optional_param('mode', 0, PARAM_INT);
$pagesize = optional_param('s', 0, PARAM_INT);
$sortorder = optional_param('o', null, PARAM_INT);

// In case we are included from the forum view page.
if (empty($forum)) {

    if (empty($forumid)) {
        $cm = \theme_imtpn\mur_pedagogique::get_cm();
        if ($cm) {
            $forum = $forumvault->get_from_course_module_id($cm->id);
        }
        if (empty($forum)) {
            throw new \moodle_exception('Unable to find forum with cmid ' . $cm->id);
        }
    } else {
        $forum = $forumvault->get_from_id($forumid);
        if (empty($forum)) {
            throw new \moodle_exception('Unable to find forum with id ' . $forumid);
        }
    }
}

\theme_imtpn\mur_pedagogique::display_wall($forum,
    $managerfactory,
    $legacydatamapperfactory,
    $discussionlistvault,
    $postvault,
    $mode,
    $search,
    $sortorder,
    $pageno,
    $pagesize);
