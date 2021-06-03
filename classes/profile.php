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
 * Profile specific routines
 *
 * @package   theme_imtpn
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_imtpn;

use context_course;
use context_helper;
use context_system;
use context_user;
use core_tag_tag;
use core_user;
use core_user\output\myprofile\category;
use core_user\output\myprofile\node;
use core_user\output\myprofile\tree;
use html_writer;
use moodle_url;
use stdClass;
use theme_imtpn\local\utils;
use theme_imtpn\output\courses_thumbnails;

defined('MOODLE_INTERNAL') || die();

/**
 * Class mur_pedagogique
 *
 * @package theme_imtpn
 */
class profile {

    public static function inject_scss($themename) {
        $profileimageurl = utils::get_profile_page_image_url($themename);
        if (empty($profileimageurl)) {
            $profileimageurl[utils::IMAGE_SIZE_TYPE_NORMAL] = '[[pix:theme|backgrounds/profile]]';
            $profileimageurl[utils::IMAGE_SIZE_TYPE_LG] = '[[pix:theme|backgrounds/profile-2x]]';
            $profileimageurl[utils::IMAGE_SIZE_TYPE_XL] = '[[pix:theme|backgrounds/profile-3x]]';
        }
        $profileimagedef = '
        .pagelayout-mypublic {
            #page-header {
            ';
        foreach ($profileimageurl as $type => $def) {
            $bgdef = "
            background-size: cover;
            background-image: url($def);";
            if ($type != utils::IMAGE_SIZE_TYPE_NORMAL) {
                $profileimagedef .= " @include media-breakpoint-up($type) {
                $bgdef
             }";
            } else {
                $profileimagedef .= $bgdef;
            }

        }
        $profileimagedef .= '
            }
        }';
        return $profileimagedef;
    }

    /**
     * Parse all callbacks and builds the tree.
     *
     * @param integer $user ID of the user for which the profile is displayed.
     * @param bool $iscurrentuser true if the profile being viewed is of current user, else false.
     * @param null $course Course object
     *
     * @return tree Fully build tree to be rendered on my profile page.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function build_tree($user, $iscurrentuser, $course = null) {
        global $CFG;
        $tree = new tree();

        // Add core nodes.

        require_once($CFG->libdir . "/myprofilelib.php");

        self::core_myprofile_navigation($tree, $user, $iscurrentuser, $course);

        // Core components.
        $components = \core_component::get_core_subsystems();
        foreach ($components as $component => $directory) {
            if (empty($directory)) {
                continue;
            }
            if (!self::check_display($component)) {
                continue;
            }
            $file = $directory . "/lib.php";
            if (is_readable($file)) {
                require_once($file);
                $function = "core_" . $component . "_myprofile_navigation";
                if (function_exists($function)) {
                    $function($tree, $user, $iscurrentuser, $course);
                }
            }
        }

        // Plugins.
        $pluginswithfunction = get_plugins_with_function('myprofile_navigation', 'lib.php');
        foreach ($pluginswithfunction as $component => $plugins) {
            if (!self::check_display($component)) {
                continue;
            }
            foreach ($plugins as $module => $function) {
                if (!self::check_display($component, $module)) {
                    continue;
                }

                $function($tree, $user, $iscurrentuser, $course);
            }
        }

        $tree->sort_categories();
        return $tree;
    }

    /**
     * Return true if the component can be displayed
     *
     * @param string $component a name of a component of the profile page.
     * @param null $module a module name (for subcomponents)
     * @return bool
     * @throws \dml_exception
     */
    protected static function check_display($component, $module = null) {
        static $simplified = null, $excludedcomponents = null, $excludedmodules = null;
        if (is_null($simplified)) {
            $simplified = get_config('theme_imtpn', 'simplifiedprofilepage');
        }
        if (is_null($excludedcomponents)) {
            $excludedcomponentscfg = get_config('theme_imtpn', 'profilecomponentsexclusion');
            $excludedcomponents = array_map('trim',
                explode(',', $excludedcomponentscfg ? $excludedcomponentscfg : ''));
        }
        if (is_null($excludedmodules)) {
            $excludedmodulescfg = get_config('theme_imtpn', 'profilemodulessexclusion');
            $excludedmodules = array_map('trim',
                explode(',', $excludedmodulescfg ? $excludedmodulescfg : ''));
        }
        if ($simplified) {
            if (empty($module)) {
                return !in_array($component, $excludedcomponents);
            } else {
                return !in_array("{$component}_{$module}", $excludedmodules);
            }
        }
        return !$simplified;
    }

    /**
     * Defines core nodes for my profile navigation tree.
     *
     * @param tree $tree Tree object
     * @param stdClass $user user object
     * @param bool $iscurrentuser is the user viewing profile, current user ?
     * @param stdClass $course course object
     *
     * @return bool
     */
    public static function core_myprofile_navigation(tree $tree, $user, $iscurrentuser, $course) {
        global $CFG, $USER, $DB, $PAGE, $OUTPUT;

        $usercontext = context_user::instance($user->id, MUST_EXIST);
        $systemcontext = context_system::instance();
        $courseorusercontext = !empty($course) ? context_course::instance($course->id) : $usercontext;
        $courseorsystemcontext = !empty($course) ? context_course::instance($course->id) : $systemcontext;
        $courseid = !empty($course) ? $course->id : SITEID;

        $contactcategory = new category('contact', get_string('userinfos', 'theme_imtpn'), '',
            ' profile-contact');
        // No after property specified intentionally. It is a hack to make administration block appear towards the end
        // Refer MDL-49928.
        $coursedetailscategory = new category('coursedetails', get_string('coursedetails'));
        $miscategory = new category('miscellaneous', get_string('miscellaneous'), 'coursedetails');
        $reportcategory = new category('reports', get_string('reports'), 'miscellaneous');
        $admincategory = new category('administration', get_string('administration'), 'reports');
        $loginactivitycategory = new category('loginactivity', get_string('loginactivity'), 'administration');

        // Add categories.
        $tree->add_category($contactcategory);
        $tree->add_category($coursedetailscategory);
        $tree->add_category($miscategory);
        $tree->add_category($reportcategory);
        $tree->add_category($admincategory);
        $tree->add_category($loginactivitycategory);

        // Add core nodes.
        // Full profile node.
        if (!empty($course)) {
            if (self::check_display('miscellaneous')) {
                if (user_can_view_profile($user, null, $usercontext)) {
                    $url = new moodle_url('/user/profile.php', array('id' => $user->id));
                    $node = new node('miscellaneous', 'fullprofile',
                        get_string('fullprofile'), null, $url);
                    $tree->add_node($node);
                }
            }
        }

        // Preference page.
        if (!$iscurrentuser && $PAGE->settingsnav->can_view_user_preferences($user->id)) {
            $url = new moodle_url('/user/preferences.php', array('userid' => $user->id));
            $title = get_string('preferences', 'moodle');
            $node = new node('administration', 'preferences', $title, null, $url);
            $tree->add_node($node);
        }

        // Login as ...
        if (!$user->deleted && !$iscurrentuser &&
            !\core\session\manager::is_loggedinas() && has_capability('moodle/user:loginas',
                $courseorsystemcontext) && !is_siteadmin($user->id)) {
            $url = new moodle_url('/course/loginas.php',
                array('id' => $courseid, 'user' => $user->id, 'sesskey' => sesskey()));
            $node = new  node('administration', 'loginas', get_string('loginas'), null, $url);
            $tree->add_node($node);
        }

        // Contact details.
        if (has_capability('moodle/user:viewhiddendetails', $courseorusercontext)) {
            $hiddenfields = array();
        } else {
            $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
        }
        $canviewuseridentity = has_capability('moodle/site:viewuseridentity', $courseorusercontext);
        if ($canviewuseridentity) {
            $identityfields = array_flip(explode(',', $CFG->showuseridentity));
        } else {
            $identityfields = array();
        }

        if (is_mnet_remote_user($user)) {
            $sql = "SELECT h.id, h.name, h.wwwroot,
                       a.name as application, a.display_name
                  FROM {mnet_host} h, {mnet_application} a
                 WHERE h.id = ? AND h.applicationid = a.id";

            $remotehost = $DB->get_record_sql($sql, array($user->mnethostid));
            $remoteuser = new stdclass();
            $remoteuser->remotetype = $remotehost->display_name;
            $hostinfo = new stdclass();
            $hostinfo->remotename = $remotehost->name;
            $hostinfo->remoteurl = $remotehost->wwwroot;

            $node = new node('contact', 'mnet', get_string('remoteuser', 'mnet', $remoteuser),
                null, null,
                get_string('remoteuserinfo', 'mnet', $hostinfo), null, 'remoteuserinfo');
            $tree->add_node($node);
        }

        if ($iscurrentuser
            or (!isset($hiddenfields['email']) and (
                    $user->maildisplay == core_user::MAILDISPLAY_EVERYONE
                    or ($user->maildisplay == core_user::MAILDISPLAY_COURSE_MEMBERS_ONLY and enrol_sharing_course($user, $USER))
                    or has_capability('moodle/course:useremail', $courseorusercontext) // TODO: Deprecate/remove for MDL-37479.
                ))
            or (isset($identityfields['email']) and $canviewuseridentity)
        ) {
            $node = new node('contact', 'email', '', null, null,
                obfuscate_mailto($user->email, ''), new \pix_icon('t/email', get_string('email')));
            $tree->add_node($node);
        }

        if (!isset($hiddenfields['moodlenetprofile']) && $user->moodlenetprofile) {
            $node = new node('contact', 'moodlenetprofile', get_string('moodlenetprofile', 'user'),
                null,
                null, $user->moodlenetprofile);
            $tree->add_node($node);
        }

        if (!isset($hiddenfields['country']) && $user->country) {
            $node = new node('contact', 'country', '', null, null,
                get_string($user->country, 'countries'), new \pix_icon('t/sendmessage', get_string('country')));
            $tree->add_node($node);
        }

        if (!isset($hiddenfields['city']) && $user->city) {
            $node = new node('contact', 'city', get_string('city'), null, null, $user->city);
            $tree->add_node($node);
        }

        if (isset($identityfields['address']) && $user->address) {
            $node = new node('contact', 'address', get_string('address'), null, null, $user->address);
            $tree->add_node($node);
        }

        if (isset($identityfields['phone1']) && $user->phone1) {
            $node = new node('contact', 'phone1', get_string('phone1'), null, null, $user->phone1);
            $tree->add_node($node);
        }

        if (isset($identityfields['phone2']) && $user->phone2) {
            $node = new node('contact', 'phone2', get_string('phone2'), null, null, $user->phone2);
            $tree->add_node($node);
        }

        if (isset($identityfields['institution']) && $user->institution) {
            $node = new node('contact', 'institution', get_string('institution'), null, null,
                $user->institution);
            $tree->add_node($node);
        }

        if (isset($identityfields['department']) && $user->department) {
            $node = new node('contact', 'department', get_string('department'), null, null,
                $user->department);
            $tree->add_node($node);
        }

        if (isset($identityfields['idnumber']) && $user->idnumber) {
            $node = new node('contact', 'idnumber', get_string('idnumber'), null, null,
                $user->idnumber);
            $tree->add_node($node);
        }

        if ($user->url && !isset($hiddenfields['webpage'])) {
            $url = $user->url;
            if (strpos($user->url, '://') === false) {
                $url = 'http://' . $url;
            }
            $webpageurl = new moodle_url($url);
            $node = new node('contact', 'webpage', get_string('webpage'), null, null,
                html_writer::link($url, $webpageurl));
            $tree->add_node($node);
        }

        // Printing tagged interests. We want this only for full profile.
        if (empty($course) && ($interests = core_tag_tag::get_item_tags('core', 'user', $user->id))) {
            $node = new node('contact', 'interests', get_string('interests'), null, null,
                $OUTPUT->tag_list($interests, ''));
            $tree->add_node($node);
        }

        if ($iscurrentuser || !isset($hiddenfields['mycourses'])) {
            $showallcourses = optional_param('showallcourses', 0, PARAM_INT);
            if ($mycourses = enrol_get_all_users_courses($user->id, true, null)) {
                $shown = 0;
                $coursesid = [];
                $showmorelink = false;
                foreach ($mycourses as $mycourse) {
                    if ($mycourse->category) {
                        context_helper::preload_from_record($mycourse);
                        $ccontext = context_course::instance($mycourse->id);
                        if (!isset($course) || $mycourse->id != $course->id) {
                            $linkattributes = null;
                            if ($mycourse->visible == 0) {
                                if (!has_capability('moodle/course:viewhiddencourses', $ccontext)) {
                                    continue;
                                }
                                $linkattributes['class'] = 'dimmed';
                            }
                            $coursesid[] = $mycourse->id;
                        }
                    }
                    $shown++;
                    if (!$showallcourses && $shown == $CFG->navcourselimit) {
                        $showmorelink = true;
                        break;
                    }
                }
                $coursethumnails = new courses_thumbnails($coursesid);
                $courselisting = $OUTPUT->render($coursethumnails);
                if ($showmorelink) {
                    $url = null;
                    if (isset($course)) {
                        $url = new moodle_url('/user/view.php',
                            array('id' => $user->id, 'course' => $course->id, 'showallcourses' => 1));
                    } else {
                        $url = new moodle_url('/user/profile.php', array('id' => $user->id, 'showallcourses' => 1));
                    }
                    $courselisting .= html_writer::tag('li', html_writer::link($url, get_string('viewmore'),
                        array('title' => get_string('viewmore'))), array('class' => 'viewmore'));
                }
                if (!empty($mycourses)) {
                    // Add this node only if there are courses to display.
                    $node = new node('coursedetails', 'courseprofiles',
                        get_string('courseprofiles'), null, null, rtrim($courselisting, ', '));
                    $tree->add_node($node);
                }
            }
        }

        if (!empty($course)) {

            // Show roles in this course.
            if ($rolestring = get_user_roles_in_course($user->id, $course->id)) {
                $node = new node('coursedetails', 'roles', get_string('roles'), null, null,
                    $rolestring);
                $tree->add_node($node);
            }

            // Show groups this user is in.
            if (!isset($hiddenfields['groups']) && !empty($course)) {
                $accessallgroups = has_capability('moodle/site:accessallgroups', $courseorsystemcontext);
                if ($usergroups = groups_get_all_groups($course->id, $user->id)) {
                    $groupstr = '';
                    foreach ($usergroups as $group) {
                        if ($course->groupmode == SEPARATEGROUPS and !$accessallgroups and $user->id != $USER->id) {
                            if (!groups_is_member($group->id, $user->id)) {
                                continue;
                            }
                        }

                        if ($course->groupmode != NOGROUPS) {
                            $groupstr .= ' <a href="' . $CFG->wwwroot . '/user/index.php?id=' . $course->id . '&amp;group=' .
                                $group->id . '">'
                                . format_string($group->name) . '</a>,';
                        } else {
                            // The user/index.php shows groups only when course in group mode.
                            $groupstr .= ' ' . format_string($group->name);
                        }
                    }
                    if ($groupstr !== '') {
                        $node = new node('coursedetails', 'groups',
                            get_string('group'), null, null, rtrim($groupstr, ', '));
                        $tree->add_node($node);
                    }
                }
            }

            if (!isset($hiddenfields['suspended'])) {
                if ($user->suspended) {
                    $node = new node('coursedetails', 'suspended',
                        null, null, null, get_string('suspended', 'auth'));
                    $tree->add_node($node);
                }
            }
        }

        if ($user->icq && !isset($hiddenfields['icqnumber'])) {
            $imurl = new moodle_url('https://web.icq.com/wwp', array('uin' => $user->icq));
            $iconurl = new moodle_url('https://web.icq.com/whitepages/online', array('icq' => $user->icq, 'img' => '5'));
            $statusicon = html_writer::tag('img', '',
                array('src' => $iconurl, 'class' => 'icon icon-post', 'alt' => get_string('status')));
            $node = new node('contact', 'icqnumber', get_string('icqnumber'), null, null,
                html_writer::link($imurl, s($user->icq) . $statusicon));
            $tree->add_node($node);
        }

        if ($user->skype && !isset($hiddenfields['skypeid'])) {
            $imurl = 'skype:' . urlencode($user->skype) . '?call';
            $node = new node('contact', 'skypeid', get_string('skypeid'), null, null,
                html_writer::link($imurl, s($user->skype)));
            $tree->add_node($node);
        }
        if ($user->yahoo && !isset($hiddenfields['yahooid'])) {
            $node = new node('contact', 'yahooid', get_string('yahooid'), null, null,
                s($user->yahoo));
            $tree->add_node($node);
        }
        if ($user->aim && !isset($hiddenfields['aimid'])) {
            $node = new node('contact', 'aimid', get_string('aimid'), null, null,
                s($user->aim));
            $tree->add_node($node);
        }
        if ($user->msn && !isset($hiddenfields['msnid'])) {
            $node = new node('contact', 'msnid', get_string('msnid'), null, null,
                s($user->msn));
            $tree->add_node($node);
        }

        $categories = profile_get_user_fields_with_data_by_category($user->id);
        foreach ($categories as $categoryid => $fields) {
            foreach ($fields as $formfield) {
                if ($formfield->is_visible() and !$formfield->is_empty()) {
                    $node = new node('contact', 'custom_field_' . $formfield->field->shortname,
                        format_string($formfield->field->name), null, null, $formfield->display_data());
                    $tree->add_node($node);
                }
            }
        }

        // First access. (Why only for sites ?)
        if (!isset($hiddenfields['firstaccess']) && empty($course)) {
            if ($user->firstaccess) {
                $datestring = userdate($user->firstaccess) . "&nbsp; (" . format_time(time() - $user->firstaccess) . ")";
            } else {
                $datestring = get_string("never");
            }
            if (static::check_display('loginactivity')) {
                $node = new node('loginactivity', 'firstaccess', get_string('firstsiteaccess'),
                    null, null,
                    $datestring);
                $tree->add_node($node);
            }
        }

        // Last access.
        if (!isset($hiddenfields['lastaccess'])) {
            if (empty($course)) {
                $string = get_string('lastsiteaccess');
                if ($user->lastaccess) {
                    $datestring = userdate($user->lastaccess) . "&nbsp; (" . format_time(time() - $user->lastaccess) . ")";
                } else {
                    $datestring = get_string("never");
                }
            } else {
                $string = get_string('lastcourseaccess');
                if ($lastaccess = $DB->get_record('user_lastaccess',
                    array('userid' => $user->id, 'courseid' => $course->id))) {
                    $datestring =
                        userdate($lastaccess->timeaccess) . "&nbsp; (" . format_time(time() - $lastaccess->timeaccess) . ")";
                } else {
                    $datestring = get_string("never");
                }
            }

            if (static::check_display('loginactivity')) {
                $node = new node('loginactivity', 'lastaccess', $string, null, null,
                    $datestring);
                $tree->add_node($node);
            }
        }

        // Last ip.
        if (has_capability('moodle/user:viewlastip', $usercontext) && !isset($hiddenfields['lastip'])) {
            if ($user->lastip) {
                $iplookupurl = new moodle_url('/iplookup/index.php', array('ip' => $user->lastip, 'user' => $user->id));
                $ipstring = html_writer::link($iplookupurl, $user->lastip);
            } else {
                $ipstring = get_string("none");
            }
            if (static::check_display('loginactivity')) {
                $node = new node('loginactivity', 'lastip', get_string('lastip'), null, null,
                    $ipstring);
                $tree->add_node($node);
            }
        }
    }
}