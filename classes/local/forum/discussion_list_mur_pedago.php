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
 * Discussion list renderer.
 *
 * @package    mod_forum
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_imtpn\local\forum;

use cm_info;
use core\output\notification;
use html_writer;
use mod_forum\grades\forum_gradeitem;
use mod_forum\local\container;
use mod_forum\local\entities\forum as forum_entity;
use mod_forum\local\factories\builder as builder_factory;
use mod_forum\local\factories\exporter as exporter_factory;
use mod_forum\local\factories\legacy_data_mapper as legacy_data_mapper_factory;
use mod_forum\local\factories\url as url_factory;
use mod_forum\local\factories\vault as vault_factory;
use mod_forum\local\managers\capability as capability_manager;
use mod_forum\local\vaults\discussion_list as discussion_list_vault;
use renderer_base;
use stdClass;
use theme_imtpn\local\utils;
use theme_imtpn\mur_pedagogique;

defined('MOODLE_INTERNAL') || die();

/**
 * The discussion list renderer.
 *
 * @package    mod_forum
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class discussion_list_mur_pedago {
    // NOTE: Real shame here as we could have inherited from  mod_forum\local\renderers\discussion_list
    // BUT everything was private, including the bits we wanted to change/
    // We should have had a split between the  renderer and the export_for_template function instead...
    // To be reviewed on mod_form upgrades.

    /** @var forum_entity The forum being rendered */
    private $forum;

    /** @var stdClass The DB record for the forum being rendered */
    private $forumrecord;

    /** @var renderer_base The renderer used to render the view */
    private $renderer;

    /** @var legacy_data_mapper_factory $legacydatamapperfactory Legacy data mapper factory */
    private $legacydatamapperfactory;

    /** @var exporter_factory $exporterfactory Exporter factory */
    private $exporterfactory;

    /** @var vault_factory $vaultfactory Vault factory */
    private $vaultfactory;

    /** @var capability_manager $capabilitymanager Capability manager */
    private $capabilitymanager;

    /** @var url_factory $urlfactory URL factory */
    private $urlfactory;

    /** @var array $notifications List of notification HTML */
    private $notifications;

    /** @var builder_factory $builderfactory Builder factory */
    private $builderfactory;

    /** @var callable $postprocessfortemplate Function to process exported posts before template rendering */
    private $postprocessfortemplate;

    /** @var string $template The template to use when displaying */
    private $template;

    /** @var gradeitem The gradeitem instance associated with this forum */
    private $forumgradeitem;

    /**
     * Constructor for a new discussion list renderer.
     *
     * @param forum_entity $forum The forum entity to be rendered
     * @param renderer_base $renderer The renderer used to render the view
     * @param string $template
     * @param notification[] $notifications A list of any notifications to be displayed within the page
     */
    public function __construct(
        forum_entity $forum,
        renderer_base $renderer,
        array $notifications = []
    ) {
        $forumgradeitem = forum_gradeitem::load_from_forum_entity($forum);
        $managerfactory = container::get_manager_factory();
        $capabilitymanager = $managerfactory->get_capability_manager($forum);
        $exporterfactory = container::get_exporter_factory();
        $vaultfactory = container::get_vault_factory();
        $builderfactory = container::get_builder_factory();
        $legacydatamapperfactory = container::get_legacy_data_mapper_factory();
        $urlfactory = container::get_url_factory();
        $exportedpostssorter = container::get_entity_factory()->get_exported_posts_sorter();

        $this->template = 'theme_imtpn/murpedago_discussion_list';

        $postprocessfortemplate =
            function($discussions, $user, $forum) use ($capabilitymanager,
                $builderfactory,
                $vaultfactory,
                $legacydatamapperfactory,
                $exportedpostssorter
            ) {
                $exportedpostsbuilder = $builderfactory->get_exported_posts_builder();
                $discussionentries = [];
                $postentries = [];
                $postvault = $vaultfactory->get_post_vault();
                $orderpostsby ='created ASC';
                foreach ($discussions as $discussion) {
                    $discussionentries[$discussion->get_discussion()->get_id()] = $discussion->get_discussion();
                    $firstpost  = $discussion->get_first_post();
                    $replies = $postvault->get_replies_to_post($user, $firstpost,
                        $capabilitymanager->can_view_any_private_reply($user), $orderpostsby);
                    $postentries = array_merge($postentries, [$firstpost], $replies);
                }

                $exportedposts['posts'] = $exportedpostsbuilder->build(
                    $user,
                    [$forum],
                    array_values($discussionentries),
                    $postentries
                );

                $postvault = $vaultfactory->get_post_vault();
                $canseeanyprivatereply = $capabilitymanager->can_view_any_private_reply($user);
                $discussionrepliescount = $postvault->get_reply_count_for_discussion_ids(
                    $user,
                    array_keys($discussionentries),
                    $canseeanyprivatereply
                );
                $forumdatamapper = $legacydatamapperfactory->get_forum_data_mapper();
                $forumrecord = $forumdatamapper->to_legacy_object($forum);
                if (forum_tp_is_tracked($forumrecord, $user)) {
                    $discussionunreadscount = $postvault->get_unread_count_for_discussion_ids(
                        $user,
                        array_keys($discussionentries),
                        $canseeanyprivatereply
                    );
                } else {
                    $discussionunreadscount = [];
                }

                array_walk($exportedposts['posts'], function($post) use ($discussionrepliescount,
                    $discussionunreadscount,
                    $discussionentries) {
                    $post->discussionrepliescount = $discussionrepliescount[$post->discussionid] ?? 0;
                    $post->discussionunreadscount = $discussionunreadscount[$post->discussionid] ?? 0;
                    // TODO: Find a better solution due to language differences when defining the singular and plural form.
                    $post->isreplyplural = $post->discussionrepliescount != 1 ? true : false;
                    $post->isunreadplural = $post->discussionunreadscount != 1 ? true : false;
                    $groups = []; // Cache the group names;
                    if (!empty($discussionentries[$post->discussionid])) {
                        $groupid = $discussionentries[$post->discussionid]->get_group_id();
                        $courseid = $discussionentries[$post->discussionid]->get_course_id();
                        if (!empty($groupid)) {
                            if ($groupid === -1) {
                                $post->subject .= html_writer::span('&nbsp; '.get_string('nogroup', 'group'));
                            } else {
                                if (empty($groups[$groupid])) {
                                    $group = groups_get_group($groupid);
                                    $groups[$groupid] = $group;
                                } else if ($groupid == -1) {
                                }
                                $post->subject .= html_writer::span('&nbsp;')
                                    . mur_pedagogique::get_group_link($groups[$groupid], $courseid, false);
                            }
                        }
                    }
                });

                $sortedposts = $exportedpostssorter->sort_into_children($exportedposts['posts']);
                $sortintoreplies = function($nestedposts) use (&$sortintoreplies) {
                    return array_map(function($postdata) use (&$sortintoreplies) {
                        [$post, $replies] = $postdata;
                        $totalreplycount = 0;

                        if (empty($replies)) {
                            $post->replies = [];
                            $post->hasreplies = false;
                        } else {
                            $sortedreplies = $sortintoreplies($replies);
                            // Set the parent author name on the replies. This is used for screen
                            // readers to help them identify the structure of the discussion.
                            $sortedreplies = array_map(function($reply) use ($post) {
                                if (isset($post->author)) {
                                    $reply->parentauthorname = $post->author->fullname;
                                } else {
                                    // The only time the author won't be set is for a single discussion
                                    // forum. See above for where it gets unset.
                                    $reply->parentauthorname = get_string('firstpost', 'mod_forum');
                                }
                                return $reply;
                            }, $sortedreplies);

                            $totalreplycount = array_reduce($sortedreplies, function($carry, $reply) {
                                return $carry + 1 + $reply->totalreplycount;
                            }, $totalreplycount);

                            $post->replies = $sortedreplies;
                            $post->hasreplies = true;
                        }

                        $post->totalreplycount = $totalreplycount;

                        return $post;
                    }, $nestedposts);
                };
                // Set the "replies" property on the exported posts.
                $exportedposts['posts'] = $sortintoreplies($sortedposts);

                $exportedposts['state']['hasdiscussions'] = $exportedposts['posts'] ? true : false;

                return $exportedposts;
            };

        $this->forum = $forum;
        $this->renderer = $renderer;
        $this->legacydatamapperfactory = $legacydatamapperfactory;
        $this->exporterfactory = $exporterfactory;
        $this->vaultfactory = $vaultfactory;
        $this->builderfactory = $builderfactory;
        $this->capabilitymanager = $capabilitymanager;

        $this->urlfactory = $urlfactory;
        $this->notifications = $notifications;
        $this->postprocessfortemplate = $postprocessfortemplate;
        $this->forumgradeitem = $forumgradeitem;

        $forumdatamapper = $this->legacydatamapperfactory->get_forum_data_mapper();
        $this->forumrecord = $forumdatamapper->to_legacy_object($forum);
    }

    /**
     * Render for the specified user.
     *
     * @param stdClass $user The user to render for
     * @param cm_info $cm The course module info for this discussion list
     * @param int $groupid The group to render
     * @param int $sortorder The sort order to use when selecting the discussions in the list
     * @param int $pageno The zero-indexed page number to use
     * @param int $pagesize The number of discussions to show on the page
     * @param int $displaymode The discussion display mode
     * @return  string      The rendered content for display
     */
    public function render(
        stdClass $user,
        cm_info $cm,
        ?int $groupid,
        ?int $sortorder,
        ?int $pageno,
        ?int $pagesize,
        int $displaymode = FORUM_MODE_NESTED_V2
    ): string {
        global $PAGE;

        $forum = $this->forum;
        $course = $forum->get_course_record();

        $forumexporter = $this->exporterfactory->get_forum_exporter(
            $user,
            $this->forum,
            $groupid
        );

        $pagesize = $this->get_page_size($pagesize);
        $pageno = $this->get_page_number($pageno);

        // Count all forum discussion posts.
        $alldiscussionscount = mod_forum_count_all_discussions($forum, $user, $groupid);

        // Get all forum discussion summaries.
        $discussions = mod_forum_get_discussion_summaries($forum, $user, $groupid, $sortorder, $pageno, $pagesize);

        $capabilitymanager = $this->capabilitymanager;
        $hasanyactions = false;
        $hasanyactions = $hasanyactions || $capabilitymanager->can_favourite_discussion($user);
        $hasanyactions = $hasanyactions || $capabilitymanager->can_pin_discussions($user);
        $hasanyactions = $hasanyactions || $capabilitymanager->can_manage_forum($user);

        $forumview = [
            'forum' => (array) $forumexporter->export($this->renderer),
            'contextid' => $forum->get_context()->id,
            'cmid' => $cm->id,
            'name' => $forum->get_name(),
            'courseid' => $course->id,
            'coursename' => $course->shortname,
            'experimentaldisplaymode' => $displaymode == FORUM_MODE_NESTED_V2,
            'gradingcomponent' => $this->forumgradeitem->get_grading_component_name(),
            'gradingcomponentsubtype' => $this->forumgradeitem->get_grading_component_subtype(),
            'sendstudentnotifications' => $forum->should_notify_students_default_when_grade_for_forum(),
            'hasanyactions' => $hasanyactions,
            'groupchangemenu' => '', // No group change menu here.
            'hasmore' => ($alldiscussionscount > $pagesize),
            'notifications' => $this->get_notifications($user, $groupid),
            'settings' => [
                'excludetext' => true,
                'togglemoreicon' => true,
                'excludesubscription' => true
            ],
            'totaldiscussioncount' => $alldiscussionscount,
            'userid' => $user->id,
            'visiblediscussioncount' => count($discussions)
        ];

        if ($forumview['forum']['capabilities']['create']) {
            $forumview['newdiscussionhtml'] = $this->get_discussion_form($user, $cm, $groupid);
        }

        if (!$discussions) {
            return $this->renderer->render_from_template($this->template, $forumview);
        }

        if ($this->postprocessfortemplate !== null) {
            // We've got some post processing to do!
            $exportedposts = ($this->postprocessfortemplate) ($discussions, $user, $forum);
        }

        $baseurl = new \moodle_url($PAGE->url, array('o' => $sortorder));

        $forumview = array_merge(
            $forumview,
            [
                'pagination' => $this->renderer->render(new \paging_bar($alldiscussionscount, $pageno, $pagesize, $baseurl, 'p')),
            ],
            $exportedposts
        );

        $firstdiscussion = reset($discussions);
        $forumview['firstgradeduserid'] = $firstdiscussion->get_latest_post_author()->get_id();

        return $this->renderer->render_from_template($this->template, $forumview);
    }

    /**
     * Get the mod_forum_post_form. This is the default boiler plate from mod_forum/post_form.php with the inpage flag caveat
     *
     * @param stdClass $user The user the form is being generated for
     * @param \cm_info $cm
     * @param int $groupid The groupid if any
     *
     * @return string The rendered html
     */
    private function get_discussion_form(stdClass $user, \cm_info $cm, ?int $groupid) {
        $forum = $this->forum;
        $forumrecord = $this->legacydatamapperfactory->get_forum_data_mapper()->to_legacy_object($forum);
        $modcontext = \context_module::instance($cm->id);
        $coursecontext = \context_course::instance($forum->get_course_id());
        $post = (object) [
            'course' => $forum->get_course_id(),
            'forum' => $forum->get_id(),
            'discussion' => 0,           // Ie discussion # not defined yet.
            'parent' => 0,
            'subject' => '',
            'userid' => $user->id,
            'message' => '',
            'messageformat' => editors_get_preferred_format(),
            'messagetrust' => 0,
            'groupid' => $groupid,
        ];
        $thresholdwarning = forum_check_throttling($forumrecord, $cm);

        $formparams = array(
            'course' => $forum->get_course_record(),
            'cm' => $cm,
            'coursecontext' => $coursecontext,
            'modcontext' => $modcontext,
            'forum' => $forumrecord,
            'post' => $post,
            'subscribe' => \mod_forum\subscriptions::is_subscribed($user->id, $forumrecord,
                null, $cm),
            'thresholdwarning' => $thresholdwarning,
            'inpagereply' => true,
            'edit' => 0
        );
        $posturl = new \moodle_url('/mod/forum/post.php');
        $mformpost = new \mod_forum_post_form($posturl, $formparams, 'post', '', array('id' => 'mformforum'));
        $discussionsubscribe = \mod_forum\subscriptions::get_user_default_subscription($forumrecord, $coursecontext, $cm, null);

        $params = array('reply' => 0, 'forum' => $forumrecord->id, 'edit' => 0) +
            (isset($post->groupid) ? array('groupid' => $post->groupid) : array()) +
            array(
                'userid' => $post->userid,
                'parent' => $post->parent,
                'discussion' => $post->discussion,
                'course' => $forum->get_course_id(),
                'discussionsubscribe' => $discussionsubscribe
            );
        $mformpost->set_data($params);

        return $mformpost->render();
    }

    /**
     * Fetch the page size to use when displaying the page.
     *
     * @param int $pagesize The number of discussions to show on the page
     * @return  int         The normalised page size
     */
    private function get_page_size(?int $pagesize): int {
        if (null === $pagesize || $pagesize <= 0) {
            $pagesize = discussion_list_vault::PAGESIZE_DEFAULT;
        }

        return $pagesize;
    }

    /**
     * Fetch the current page number (zero-indexed).
     *
     * @param int $pageno The zero-indexed page number to use
     * @return  int         The normalised page number
     */
    private function get_page_number(?int $pageno): int {
        if (null === $pageno || $pageno < 0) {
            $pageno = 0;
        }

        return $pageno;
    }

    /**
     * Get the list of notification for display.
     *
     * @param stdClass $user The viewing user
     * @param int|null $groupid The forum's group id
     * @return      array
     */
    private function get_notifications(stdClass $user, ?int $groupid): array {
        $notifications = $this->notifications;
        $forum = $this->forum;
        $renderer = $this->renderer;
        $capabilitymanager = $this->capabilitymanager;

        if ($forum->is_cutoff_date_reached()) {
            $notifications[] = (new notification(
                get_string('cutoffdatereached', 'forum'),
                notification::NOTIFY_INFO
            ))->set_show_closebutton();
        } else if ($forum->is_due_date_reached()) {
            $notifications[] = (new notification(
                get_string('thisforumisdue', 'forum', userdate($forum->get_due_date())),
                notification::NOTIFY_INFO
            ))->set_show_closebutton();
        } else if ($forum->has_due_date()) {
            $notifications[] = (new notification(
                get_string('thisforumhasduedate', 'forum', userdate($forum->get_due_date())),
                notification::NOTIFY_INFO
            ))->set_show_closebutton();
        }

        if ($forum->has_blocking_enabled()) {
            $notifications[] = (new notification(
                get_string('thisforumisthrottled', 'forum', [
                    'blockafter' => $forum->get_block_after(),
                    'blockperiod' => get_string('secondstotime' . $forum->get_block_period())
                ])
            ))->set_show_closebutton();
        }

        if ($forum->is_in_group_mode() && !$capabilitymanager->can_access_all_groups($user)) {
            if ($groupid === null) {
                if (!$capabilitymanager->can_post_to_my_groups($user)) {
                    $notifications[] = (new notification(
                        get_string('cannotadddiscussiongroup', 'mod_forum'),
                        \core\output\notification::NOTIFY_WARNING
                    ))->set_show_closebutton();
                } else {
                    $notifications[] = (new notification(
                        get_string('cannotadddiscussionall', 'mod_forum'),
                        \core\output\notification::NOTIFY_WARNING
                    ))->set_show_closebutton();
                }
            } else if (!$capabilitymanager->can_access_group($user, $groupid)) {
                $notifications[] = (new notification(
                    get_string('cannotadddiscussion', 'mod_forum'),
                    \core\output\notification::NOTIFY_WARNING
                ))->set_show_closebutton();
            }
        }

        if ('qanda' === $forum->get_type() && !$capabilitymanager->can_manage_forum($user)) {
            $notifications[] = (new notification(
                get_string('qandanotify', 'forum'),
                notification::NOTIFY_INFO
            ))->set_show_closebutton();
        }

        if ('eachuser' === $forum->get_type()) {
            $notifications[] = (new notification(
                get_string('allowsdiscussions', 'forum'),
                notification::NOTIFY_INFO)
            )->set_show_closebutton();
        }

        return array_map(function($notification) {
            return $notification->export_for_template($this->renderer);
        }, $notifications);
    }

}