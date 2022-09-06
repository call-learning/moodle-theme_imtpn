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
 * Mur pedagogique routines
 *
 * @package   theme_imtpn
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_imtpn;

use coding_exception;
use context_module;
use core\output\notification;
use core_availability\info_module;
use dml_exception;
use html_writer;
use mod_forum\local\container;
use cm_info;
use moodle_exception;
use moodle_page;
use moodle_url;
use navbar;
use stdClass;
use theme_imtpn\local\forum\discussion_list_mur_pedago;
use theme_imtpn\output\group_info;

/**
 * Class mur_pedagogique
 *
 * @package   theme_imtpn
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mur_pedagogique {

    /**
     * Has user access to the mur pedagogique
     *
     * @param int $userid
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function has_access($userid) {
        $hasaccess = false;
        try {
            $cm = static::get_cm();
            if ($cm) {
                $cminfo = cm_info::create($cm, $userid);
                $hasaccess = info_module::is_user_visible($cminfo->id, $userid) || is_siteadmin($userid);
            }
        } catch (moodle_exception $e) {
            debugging($e->getMessage());
        }
        return $hasaccess;
    }

    /**
     * Get CM
     *
     * @return false|mixed|stdClass
     */
    public static function get_cm() {
        global $DB;
        $murpedagogiquecm = null;
        if (self::enabled()) {
            $murpedagoidnumber = get_config('theme_imtpn', 'murpedagoidnumber');
            $murpedagogiquecm = $DB->get_record('course_modules', array('idnumber' => $murpedagoidnumber));
            if (empty($murpedagogiquecm)) {
                return null;
            }
        }
        return $murpedagogiquecm;
    }

    /**
     * Get CM
     *
     * @return false|mixed|stdClass
     * @throws dml_exception
     */
    public static function enabled() {
        global $DB;
        return get_config('theme_imtpn', 'murpedagoenabled');
    }

    /**
     * Get URL
     *
     * @return moodle_url
     */
    public static function get_url() {
        return new moodle_url('/theme/imtpn/pages/murpedagogique/index.php');
    }

    /**
     * Display Wall
     *
     * @param object $forum
     * @param int $mode
     * @param string $search
     * @param string $sortorder
     * @param int $pageno
     * @param int $pagesize
     * @throws coding_exception
     * @throws moodle_exception
     */
    public static function display_wall($forum,
        $mode,
        $search,
        $sortorder,
        $pageno,
        $pagesize) {
        global $PAGE, $OUTPUT, $CFG, $SESSION, $USER;
        $vaultfactory = container::get_vault_factory();
        $discussionlistvault = $vaultfactory->get_discussions_in_forum_vault();
        $managerfactory = container::get_manager_factory();
        $legacydatamapperfactory = container::get_legacy_data_mapper_factory();
        $urlfactory = container::get_url_factory();
        $capabilitymanager = $managerfactory->get_capability_manager($forum);

        $url = new moodle_url('/theme/imtpn/pages/murpedagogique/index.php');
        $backurl = new moodle_url('/my'); // URL to go to if any issue with visibility.
        $PAGE->set_url($url);

        $course = $forum->get_course_record();
        $coursemodule = $forum->get_course_module_record();
        $cm = cm_info::create($coursemodule);

        require_course_login($course, true, $cm);

        $istypesingle = $forum->get_type() === 'single';
        $saveddisplaymode = get_user_preferences('forum_displaymode', $CFG->forum_displaymode);

        if ($mode) {
            $displaymode = $mode;
        } else {
            $displaymode = FORUM_MODE_NESTED;
        }
        // Toggle the editing state and switches.
        if ($PAGE->user_allowed_editing()) {
            $edit = optional_param('edit', null, PARAM_BOOL);    // Turn editing on and off.
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
        $PAGE->set_context($forum->get_context());
        $PAGE->set_title($forum->get_name());
        $PAGE->add_body_class('forumtype-' . $forum->get_type());
        $PAGE->set_heading($course->fullname);
        $PAGE->set_pagelayout('incourse');
        $PAGE->set_cm($cm);
        $PAGE->set_pagetype('mod-forum-view');

        $currentbuttons = $PAGE->button;

        $currentbuttons .= $PAGE->user_allowed_editing() ? $OUTPUT->edit_button($url) : '';
        $viewallgroups = $OUTPUT->single_button(
            new moodle_url('/theme/imtpn/pages/murpedagogique/groupoverview.php'),
            get_string('viewallgroups', 'theme_imtpn'));
        $PAGE->set_button($currentbuttons . $viewallgroups);

        if ($istypesingle && $displaymode == FORUM_MODE_NESTED_V2) {
            $PAGE->add_body_class('nested-v2-display-mode reset-style');
            $settingstrigger = $OUTPUT->render_from_template('mod_forum/settings_drawer_trigger', null);
            $PAGE->add_header_action($settingstrigger);
        }

        if (empty($cm->visible) && !has_capability('moodle/course:viewhiddenactivities', $forum->get_context())) {
            redirect(
                $backurl,
                get_string('activityiscurrentlyhidden'),
                null,
                notification::NOTIFY_WARNING
            );
        }

        if (!$capabilitymanager->can_view_discussions($USER)) {
            redirect(
                $backurl,
                get_string('noviewdiscussionspermission', 'forum'),
                null,
                notification::NOTIFY_WARNING
            );
        }

        // Mark viewed and trigger the course_module_viewed event.
        $forumdatamapper = $legacydatamapperfactory->get_forum_data_mapper();
        $forumrecord = $forumdatamapper->to_legacy_object($forum);
        forum_view(
            $forumrecord,
            $forum->get_course_record(),
            $forum->get_course_module_record(),
            $forum->get_context()
        );

        // Return here if we post or set subscription etc.
        $SESSION->fromdiscussion = qualified_me();

        if (!empty($CFG->enablerssfeeds) && !empty($CFG->forum_enablerssfeeds) && $forum->get_rss_type() &&
            $forum->get_rss_articles()) {
            require_once("{$CFG->libdir}/rsslib.php");

            $rsstitle = format_string($course->shortname, true, [
                    'context' => context_course::instance($course->id),
                ]) . ': ' . format_string($forum->get_name());
            rss_add_http_header($forum->get_context(), 'mod_forum', $forumrecord, $rsstitle);
        }

        echo $OUTPUT->header();
        echo $OUTPUT->heading(format_string($forum->get_name()), 2);

        if (!$istypesingle && !empty($forum->get_intro())) {
            echo forum_search_form($course, $search);
            echo $OUTPUT->box(format_module_intro('forum', $forumrecord, $cm->id), 'generalbox', 'murpedago-intro');
        }

        $discussionsrenderer = new discussion_list_mur_pedago(
            $forum,
            $PAGE->get_renderer('mod_forum')
        );

        // Blog forums always show discussions (all groups) newest first.
        echo $discussionsrenderer->render($USER, $cm, null, $discussionlistvault::SORTORDER_CREATED_DESC,
            $pageno, $pagesize, FORUM_MODE_NESTED_V2);

        if (!$CFG->forum_usermarksread && forum_tp_is_tracked($forumrecord, $USER)) {
            $discussions = mod_forum_get_discussion_summaries($forum, $USER, null, null, $pageno, $pagesize);
            $firstpostids = array_map(function($discussion) {
                return $discussion->get_first_post()->get_id();
            }, array_values($discussions));
            forum_tp_mark_posts_read($USER, $firstpostids);
        }
        echo $OUTPUT->footer();

    }

    /**
     * Display all relevant posts
     *
     * @param object $forum
     * @param int|array $groupid
     * @param int $mode
     * @param string $search
     * @param int $sortorder
     * @param int $pageno
     * @param int $pagesize
     * @throws coding_exception
     * @throws moodle_exception
     */
    public static function display_posts($forum,
        $groupid,
        $mode,
        $search,
        $sortorder,
        $pageno,
        $pagesize) {
        global $PAGE, $CFG, $USER;

        $managerfactory = container::get_manager_factory();
        $legacydatamapperfactory = container::get_legacy_data_mapper_factory();
        $vaultfactory = container::get_vault_factory();
        $discussionlistvault = $vaultfactory->get_discussions_in_forum_vault();
        $coursemodule = $forum->get_course_module_record();
        $cm = cm_info::create($coursemodule);

        $capabilitymanager = $managerfactory->get_capability_manager($forum);

        $backurl = new moodle_url('/my'); // URL to go to if any issue with visibility.

        if ($mode) {
            $displaymode = $mode;
        } else {
            $displaymode = FORUM_MODE_NESTED;
        }

        if (empty($cm->visible) && !has_capability('moodle/course:viewhiddenactivities', $forum->get_context())) {
            redirect(
                $backurl,
                get_string('activityiscurrentlyhidden'),
                null,
                notification::NOTIFY_WARNING
            );
        }

        if (!$capabilitymanager->can_view_discussions($USER)) {
            redirect(
                $backurl,
                get_string('noviewdiscussionspermission', 'forum'),
                null,
                notification::NOTIFY_WARNING
            );
        }

        // Mark viewed and trigger the course_module_viewed event.
        $forumdatamapper = $legacydatamapperfactory->get_forum_data_mapper();
        $forumrecord = $forumdatamapper->to_legacy_object($forum);
        $notifications = [];
        $discussionsrenderer = new discussion_list_mur_pedago(
            $forum,
            $PAGE->get_renderer('mod_forum'),
            $notifications
        );

        // Blog forums always show discussions newest first.
        echo $discussionsrenderer->render($USER, $cm, $groupid, $discussionlistvault::SORTORDER_CREATED_DESC,
            $pageno, $pagesize);

        if (!$CFG->forum_usermarksread && forum_tp_is_tracked($forumrecord, $USER)) {
            $discussions = mod_forum_get_discussion_summaries($forum, $USER, null, null, $pageno, $pagesize);
            $firstpostids = array_map(function($discussion) {
                return $discussion->get_first_post()->get_id();
            }, array_values($discussions));
            forum_tp_mark_posts_read($USER, $firstpostids);
        }
    }

    /**
     * Display a group link with picture if needed.
     *
     * @param object $group
     * @param int $courseid
     * @param false $withpicture
     * @return string
     * @throws moodle_exception
     */
    public static function get_group_link(object $group, $courseid, $withpicture = false) {
        $pictureurl = get_group_picture_url($group, $courseid, false, false);
        $groupname = s($group->name);
        $content = $withpicture ? html_writer::img($pictureurl, $groupname, ['title' => $groupname])
            : html_writer::span($groupname);
        return html_writer::link(
            static::get_group_page_url($group),
            $content,
            ['class' => 'mpedago-group-link']
        );
    }

    /**
     * Get group page URL.
     *
     * @param object $group
     * @return moodle_url
     * @throws moodle_exception
     */
    public static function get_group_page_url(object $group) {
        return new moodle_url('/theme/imtpn/pages/murpedagogique/grouppage.php', array('groupid' => $group->id));
    }

    /**
     * Set additional CSS to page
     *
     * @param moodle_page $page
     * @throws coding_exception
     */
    public static function set_additional_page_classes(&$page) {
        $loggedin = isloggedin() && !isguestuser();
        if (!$loggedin) {
            $page->add_body_class('notloggedin');
        }
        $murpedaggocm = self::get_cm();
        $murpedaggocontext = null;
        if ($murpedaggocm) {
            $murpedaggocontext = context_module::instance($murpedaggocm->id);
        }
        $isonmurpedago = !empty($murpedaggocm) && !empty($page->cm->context) &&
            $page->cm->context->is_child_of($murpedaggocontext, true);
        if ($isonmurpedago) {
            $page->add_body_class('mur-pedagogique'); // We make sure a generic class is set to
            // apply custom CSS.
            $page->add_body_class('path-mod-forum'); // Make sure the usual classes apply.
            /*
             *  Also if child context, we need to make sure we adjust the breadcrumb.
             * The has_item() check is to fix an issue in navigation lib (has_items()) which
             * issues a warning in behat mode.
                    if ($page->cm->context->is_child_of($murpedaggocontext, true) && $page->navbar->has_items()) {
                        $page->navbar->ignore_active();
                    }
            */
        }
    }

    /**
     * Add relevant menu to navbar
     *
     * @param navbar $navbar
     * @param moodle_page $page
     * @return mixed|navbar
     * @throws coding_exception
     */
    public static function fix_navbar($navbar, $page) {
        global $PAGE;

        $cm = static::get_cm();
        // Check first if we are on the module page.
        $isoncm = !empty($cm) && (
                $PAGE->context->instanceid == $cm->id ||
                in_array($cm->id, $PAGE->context->get_parent_context_ids()));
        $isoncourse = !empty($cm)
            && ($PAGE->context->contextlevel === CONTEXT_COURSE &&
                $PAGE->context->get_course_context()
                && $PAGE->context->get_course_context()->instanceid == $cm->course);
        if ($isoncm || $isoncourse) {
            $allitems = [];
            foreach ($navbar->get_items() as $index => $item) {
                if (($item->parent && $item->parent->type == navbar::TYPE_ACTIVITY)
                    || ($item->isactive && $item->type != navbar::TYPE_ACTIVITY)
                    || $item->key == 'allgroups'
                ) {
                    // Working around the shortcomings of the navbar API.
                    $allitems[] = $item;
                }
            }
            $navbar = new navbar($PAGE);
            $navbar->ignore_active(true);
            $navbar->add(get_string('murpedagogique', 'theme_imtpn'),
                new moodle_url('/theme/imtpn/pages/murpedagogique/index.php'));

            // Special navigation for Participant pages.
            if ($page->docspath == 'enrol/users') {
                $groupid = optional_param('group', 0, PARAM_INT);
                static::navbar_add_groupname($navbar, $groupid);
            }
            // Special navigation for discussion pages.
            if ($page->pagetype == 'mod-forum-discuss') {
                // A bit of a hack here.
                $d = required_param('d', PARAM_INT); // Discussion ID.
                $vaultfactory = container::get_vault_factory();
                $discussionvault = $vaultfactory->get_discussion_vault();
                $discussion = $discussionvault->get_from_id($d);
                $groupid = $discussion->get_group_id();
                static::navbar_add_groupname($navbar, $groupid);
            }

            foreach ($allitems as $item) {
                $navbar->add($item->text, $item->action, $item->type, $item->shorttext, $item->key, $item->icon);
            }

        }
        return $navbar;
    }

    /**
     * Add group name
     *
     * @param object $navbar
     * @param int $groupid
     * @throws dml_exception
     * @throws moodle_exception
     */
    protected static function navbar_add_groupname(&$navbar, $groupid) {
        if ($groupid && $groupid > 0) {
            $group = groups_get_group($groupid, '*', MUST_EXIST);
            $groupname = group_info::get_group_name($group);
            $navbar->add($groupname,
                new moodle_url('/theme/imtpn/pages/murpedagogique/grouppage.php', array('groupid' => $groupid))
            );
        }
    }

}
