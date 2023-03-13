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
 * Presets management
 *
 * @package   theme_imtpn
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_imtpn\output;

use coding_exception;
use context_course;
use context_header;
use context_system;
use core_message\api;
use core_message\helper;
use core_userfeedback;
use custom_menu;
use dml_exception;
use html_writer;
use local_resourcelibrary\locallib\utils;
use moodle_exception;
use moodle_url;
use single_select;
use stdClass;
use theme_imtpn\local\custom_menu_advanced;
use theme_imtpn\mur_pedagogique;

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package   theme_imtpn
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_clboost\output\core_renderer {

    /**
     * Size of the profile image
     */
    const PROFILE_IMAGE_SIZE = 150;

    /**
     * Add more info that can then be used in the mustache template.
     *
     * For example {{# additionalinfo.isloggedin }} {{/ additionalinfo.isloggedin }}
     *
     * @return stdClass
     */
    public function get_template_additional_information() {
        $additionalinfo = parent::get_template_additional_information();
        $additionalinfo->footercontent = get_config('theme_imtpn', 'footercontent');
        return $additionalinfo;
    }

    /**
     * Get the compact logo URL.
     *
     * @return string
     */

    /**
     * Should we display the logo ?
     *
     * @return bool
     */
    public function should_display_navbar_logo() {
        $logo = $this->get_compact_logo_url();
        return !empty($logo);
    }

    /**
     * Get the compact logo URL.
     *
     * @param int $maxwidth
     * @param int $maxheight
     * @return bool|false|moodle_url
     */
    public function get_compact_logo_url($maxwidth = 100, $maxheight = 100) {
        $path = $this->get_current_theme_base_url();
        $compactlogourl = new moodle_url("{$path}/pix/logos/logo-imt-dark.png");
        if (!isloggedin() || isguestuser()) {
            // If we are not logged in, the logo should be white instead.
            $compactlogourl = new moodle_url("{$path}/pix/logos/logo-imt-white.png");
        }

        return $compactlogourl;
    }

    /**
     * Get current theme base url
     *
     * @return string
     */
    public function get_current_theme_base_url() {
        // TODO: support theme dir setting.
        if (empty($this->page->theme->name)) {
            return "/theme/imtpn";
        }
        return "/theme/{$this->page->theme->name}";
    }

    /**
     * Lang menu renderer
     *
     * {@see core_renderer::render_custom_menu()} instead.
     *
     * @param string $custommenuitems - custom menuitems set by theme instead of global theme settings
     * @return string
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function lang_menu($custommenuitems = '') {
        global $CFG;

        if (empty($CFG->langmenu)) {
            return '';
        }
        if ($this->page->course != SITEID && !empty($this->page->course->lang)) {
            // Do not show lang menu if language forced.
            return '';
        }
        $langs = get_string_manager()->get_list_of_translations();
        $haslangmenu = parent::lang_menu() != '';
        $menu = new custom_menu_advanced();
        if ($haslangmenu) {
            $strlang = get_string('language');
            $shortlangcode = current_language();
            if (isset($langs[$shortlangcode])) {
                $currentlang = $langs[$shortlangcode];
            } else {
                $currentlang = $strlang;
            }
            $this->language = $menu->add($currentlang, new moodle_url('#'), $strlang, 10000,
                "flag-icon flag-icon-{$shortlangcode}");
            foreach ($langs as $langtype => $langname) {
                $this->language->add($langname, new moodle_url($this->page->url, array('lang' => $langtype)), null,
                    null, "flag-icon flag-icon-{$langtype}");
            }
        }

        $content = '';
        foreach ($menu->get_children() as $item) {
            $context = $item->export_for_template($this);
            $content .= $this->render_from_template('core/custom_menu_item', $context);
        }

        return $content;
    }

    /**
     * Returns the custom menu if one has been set
     *
     * A custom menu can be configured by browsing to
     *    Settings: Administration > Appearance > Themes > Theme settings
     * and then configuring the custommenu config setting as described.
     *
     * Theme developers: DO NOT OVERRIDE! Please override function
     * {@see core_renderer::render_custom_menu()} instead.
     *
     * @param string $custommenuitems - custom menuitems set by theme instead of global theme settings
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function custom_menu($custommenuitems = '') {
        global $CFG;

        if (empty($custommenuitems) && !empty($CFG->custommenuitems)) {
            $custommenuitems = $CFG->custommenuitems;
        }
        $custommenu = new custom_menu_advanced($custommenuitems, current_language());
        return $this->render_custom_menu($custommenu);
    }

    /**
     * Renders a custom menu object (located in outputcomponents.php)
     *
     * The custom menu this method produces makes use of the YUI3 menunav widget
     * and requires very specific html elements and classes.
     *
     * @param custom_menu $menu
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    protected function render_custom_menu(custom_menu $menu) {
        global $CFG, $USER;

        $loggedin = isloggedin() && !isguestuser(); // Check if really logged in and not just as a guest.
        if ($loggedin) {
            $this->add_if_not_exist($menu, new moodle_url('/my'), get_string('mymoodle', 'my'));
        }
        if (isloggedin() && !isguestuser() && mur_pedagogique::has_access($USER->id)) {
            $menu->add(get_string('murpedagogique', 'theme_imtpn'), mur_pedagogique::get_url());
        }
        if (!empty($CFG->enableresourcelibrary)) {
            $this->add_if_not_exist($menu, new moodle_url('/theme/imtpn/pages/themescat.php'),
                get_string('catalogue', 'theme_imtpn'), 'fa fa-star-o catalog-menu-star');
        }
        if (!$menu->has_children()) {
            return '';
        }

        $content = '';
        foreach ($menu->get_children() as $item) {
            $context = $item->export_for_template($this);
            $content .= $this->render_from_template('core/custom_menu_item', $context);
        }

        return $content;
    }

    /**
     * Add if does not exist
     *
     * @param custom_menu_advanced $menu
     * @param moodle_url $url
     * @param string $label
     * @param string $class
     */
    protected function add_if_not_exist(custom_menu_advanced $menu, moodle_url $url, $label, $class = '') {
        foreach ($menu->get_children() as $child) {
            if ($child->get_url() === null || $url->out_omit_querystring() == $child->get_url()->out_omit_querystring()) {
                return;
            }
        }
        $menu->add($label, $url, null, null, $class);
    }

    /**
     * The standard tags (typically performance information and validation links,
     * if we are in developer debug mode) that should be output in the footer area
     * of the page. Designed to be called in theme layout.php files.
     *
     * Core change : output footer as a list instead of a just raw output.
     *
     * @return string HTML fragment.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function standard_footer_html() {
        global $CFG, $SCRIPT;

        $list = [];
        $output = '';
        if (during_initial_install()) {
            // Debugging info can not work before install is finished,
            // in any case we do not want any links during installation!
            return $output;
        }

        // Give plugins an opportunity to add any footer elements.
        // The callback must always return a string containing valid html footer content.
        $pluginswithfunction = get_plugins_with_function('standard_footer_html', 'lib.php');
        foreach ($pluginswithfunction as $plugins) {
            foreach ($plugins as $function) {
                $list[] = $function();
            }
        }

        if (core_userfeedback::can_give_feedback()) {
            $list[] = html_writer::div(
                $this->render_from_template('core/userfeedback_footer_link',
                    ['url' => core_userfeedback::make_link()->out(false)])
            ); // IMTPN: output as a list.
        }

        // This function is normally called from a layout.php file in {@see core_renderer::header()}
        // but some of the content won't be known until later, so we return a placeholder
        // for now. This will be replaced with the real content in {@see core_renderer::footer()}.
        $output .= $this->unique_performance_info_token;
        if ($this->page->devicetypeinuse == 'legacy') {
            // The legacy theme is in use print the notification.
            $list[] = html_writer::tag('div', get_string('legacythemeinuse'),
                array('class' => 'legacythemeinuse')); // IMTPN: output as a list.
        }

        // Get links to switch device types (only shown for users not on a default device).
        $list[] = $this->theme_switch_links();

        if (!empty($CFG->debugpageinfo)) {
            $list[] = '<div class="performanceinfo pageinfo">' .
                get_string('pageinfodebugsummary', 'core_admin', // IMTPN: output as a list.
                    $this->page->debug_summary()) . '</div>';
        }
        if (debugging(null, DEBUG_DEVELOPER) &&
            has_capability('moodle/site:config', context_system::instance())) {  // Only in developer mode
            // Add link to profiling report if necessary.
            if (function_exists('profiling_is_running') && profiling_is_running()) {
                $txt = get_string('profiledscript', 'admin');
                $title = get_string('profiledscriptview', 'admin');
                $url = $CFG->wwwroot . '/admin/tool/profiling/index.php?script=' . urlencode($SCRIPT);
                $link = '<a title="' . $title . '" href="' . $url . '">' . $txt . '</a>';
                $list[] = '<div class="profilingfooter">' . $link . '</div>'; // IMTPN: output as a list.
            }
            $purgeurl = new moodle_url('/admin/purgecaches.php', array('confirm' => 1,
                'sesskey' => sesskey(), 'returnurl' => $this->page->url->out_as_local_url(false)));
            $list[] = '<div class="purgecaches">' .
                html_writer::link($purgeurl, get_string('purgecaches', 'admin')) . '</div>';
            // IMTPN: output as a list.
        }
        if (!empty($CFG->debugvalidators)) {
            // NOTE: this is not a nice hack, $this->page->url is not always accurate and
            // $FULLME neither, it is not a bug if it fails. --skodak.
            $list[] = '<div class="validators"><ul class="list-unstyled ml-1">
              <li><a href="http://validator.w3.org/check?verbose=1&amp;ss=1&amp;uri='
                . urlencode(qualified_me()) . '">Validate HTML</a></li>
              <li><a href="http://www.contentquality.com/mynewtester/cynthia.exe?rptmode=-1&amp;url1='
                . urlencode(qualified_me()) . '">Section 508 Check</a></li>
              <li><a href="http://www.contentquality.com/mynewtester/cynthia.exe?rptmode=0&amp;warnp2n3e=1&amp;url1=' .
                urlencode(qualified_me()) . '">WCAG 1 (2,3) Check</a></li>
            </ul></div>'; // IMTPN: output as a list.
        }
        return html_writer::alist($list) . $output; // IMTPN: output as a list.
    }

    /**
     * Output context header.
     *
     * Core change: Change in context header to display a bigger profile image.
     * This could have been avoided if the context header was broken in two parts: the data and the renderer.
     *
     * @param null $headerinfo
     * @param int $headinglevel
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function context_header($headerinfo = null, $headinglevel = 1) {
        global $DB, $USER, $CFG, $SITE;
        require_once($CFG->dirroot . '/user/lib.php');
        $context = $this->page->context;
        $heading = null;
        $imagedata = null;
        $subheader = null;
        $userbuttons = null;

        if ($this->should_display_main_logo($headinglevel)) {
            $sitename = format_string($SITE->fullname, true, array('context' => context_course::instance(SITEID)));
            return html_writer::div(html_writer::empty_tag('img', [
                'src' => $this->get_logo_url(null, 150), 'alt' => $sitename, 'class' => 'img-fluid']), 'logo');
        }

        // Make sure to use the heading if it has been set.
        if (isset($headerinfo['heading'])) {
            $heading = $headerinfo['heading'];
        } else {
            $heading = $this->page->heading;
        }

        // The user context currently has images and buttons. Other contexts may follow.
        if (isset($headerinfo['user'])
            || $context->contextlevel == CONTEXT_USER
            || $this->page->pagelayout == 'mypublic'
        ) {
            if (isset($headerinfo['user'])) {
                $user = $headerinfo['user'];
            } else {
                // Look up the user information if it is not supplied.
                if ($context->contextlevel == CONTEXT_USER) {
                    $user = $DB->get_record('user', array('id' => $context->instanceid));
                } else {
                    $user = $USER;
                }
            }

            // If the user context is set, then use that for capability checks.
            if (isset($headerinfo['usercontext'])) {
                $context = $headerinfo['usercontext'];
            }

            // Only provide user information if the user is the current user, or a user which the current user can view.
            // When checking user_can_view_profile(), either:
            // If the page context is course, check the course context (from the page object) or;
            // If page context is NOT course, then check across all courses.
            $course = ($this->page->context->contextlevel == CONTEXT_COURSE) ? $this->page->course : null;

            if (user_can_view_profile($user, $course) || ($user->id == $USER->id)) {
                // Use the user's full name if the heading isn't set.
                if (empty($heading)) {
                    $heading = fullname($user);
                }

                $imagedata = $this->user_picture($user, array('size' => self::PROFILE_IMAGE_SIZE));

                // Check to see if we should be displaying a message button.
                if (!empty($CFG->messaging) && has_capability('moodle/site:sendmessage', $context)) {
                    $userbuttons = array(
                        'messages' => array(
                            'buttontype' => 'message',
                            'title' => get_string('message', 'message'),
                            'url' => new moodle_url('/message/index.php', array('id' => $user->id)),
                            'image' => 'message',
                            'linkattributes' => helper::messageuser_link_params($user->id),
                            'page' => $this->page
                        )
                    );

                    if ($USER->id != $user->id) {
                        $iscontact = api::is_contact($USER->id, $user->id);
                        $contacttitle = $iscontact ? 'removefromyourcontacts' : 'addtoyourcontacts';
                        $contacturlaction = $iscontact ? 'removecontact' : 'addcontact';
                        $contactimage = $iscontact ? 'removecontact' : 'addcontact';
                        $userbuttons['togglecontact'] = array(
                            'buttontype' => 'togglecontact',
                            'title' => get_string($contacttitle, 'message'),
                            'url' => new moodle_url('/message/index.php', array(
                                    'user1' => $USER->id,
                                    'user2' => $user->id,
                                    $contacturlaction => $user->id,
                                    'sesskey' => sesskey())
                            ),
                            'image' => $contactimage,
                            'linkattributes' => helper::togglecontact_link_params($user, $iscontact),
                            'page' => $this->page
                        );
                    }

                    $this->page->requires->string_for_js('changesmadereallygoaway', 'moodle');
                }
            } else {
                $heading = null;
            }
        }

        $contextheader = new context_header($heading, $headinglevel, $imagedata, $userbuttons);
        return $this->render_context_header($contextheader);
    }

    /**
     * Get Logo URL
     * If it has not been overriden by core_admin config, serve the logo in pix
     *
     * @param null $maxwidth
     * @param int $maxheight
     * @return bool|false|moodle_url
     */
    public function get_logo_url($maxwidth = null, $maxheight = 200) {
        $path = $this->get_current_theme_base_url();
        $logourl = new moodle_url("{$path}/pix/logos/logo-imt-dark.png");
        if (!isloggedin() || isguestuser()) {
            // If we are not logged in, the logo should be white instead.
            $logourl = new moodle_url("{$path}/pix/logos/logo-imt-white.png");
        }
        return $logourl;
    }

    /**
     * This renders the navbar : The change here is only in the mur pedagogique contexte.
     *
     * Uses bootstrap compatible html.
     */
    public function navbar() {
        $navbar = mur_pedagogique::fix_navbar($this->page->navbar, $this->page);
        return $this->render_from_template('core/navbar', $navbar);
    }

    /**
     * Allow for additional user menu in navigation bar in case we have no boost navbar.
     *
     * @param object $opts  navigation information object (see @user_get_user_navigation_info)
     * @param object $course
     */
    protected function additional_user_menus_nonavbar(&$opts, $course) {

        list($urltext, $url) = utils::get_catalog_url();
        $opts->navitems[] = (object) [
            'itemtype' => 'link',
            'url' => $url,
            'title' => $urltext,
            'titleidentifier' => 'resourcelibrary',
            'pix' => 'i/course'
        ];

        // Add $opts->navitems[] here.
        // Nothing for now.
    }
}
