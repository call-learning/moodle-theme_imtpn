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

use context_system;
use core_userfeedback;
use custom_menu;
use html_writer;
use moodle_url;
use single_select;
use stdClass;
use theme_imtpn\local\custom_menu_advanced;
use theme_imtpn\mur_pedagogique;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package   theme_imtpn
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_clboost\output\core_renderer {

    /**
     * Add more info that can then be used in the mustache template.
     *
     * For example {{# additionalinfo.isloggedin }} {{/ additionalinfo.isloggedin }}
     * @return stdClass
     */
    public function get_template_additional_information() {
        $additionalinfo = parent::get_template_additional_information();
        $additionalinfo->footercontent = get_config('theme_imtpn', 'footercontent');
        return $additionalinfo;
    }


    /**
     * Get Logo URL
     * If it has not been overriden by core_admin config, serve the logo in pix
     *
     * @param null $maxwidth
     * @param int $maxheight
     * @return bool|false|\moodle_url
     */
    public function get_logo_url($maxwidth = null, $maxheight = 200) {
        $logourl = new \moodle_url("/theme/imtpn/pix/logos/logo-imt-dark.png");
        if (!isloggedin() || isguestuser()) {
            // If we are not logged in, the logo should be white instead.
            $logourl = new \moodle_url("/theme/imtpn/pix/logos/logo-imt-white.png");
        }
        return $logourl;
    }

    /**
     * Get the compact logo URL.
     *
     * @return string
     */
    /**
     * Get the compact logo URL.
     *
     * @param int $maxwidth
     * @param int $maxheight
     * @return bool|false|\moodle_url
     */
    public function get_compact_logo_url($maxwidth = 100, $maxheight = 100) {
        $compactlogourl = new \moodle_url("/theme/imtpn/pix/logos/logo-imt-dark.png");
        if (!isloggedin() || isguestuser()) {
            // If we are not logged in, the logo should be white instead.
            $compactlogourl = new \moodle_url("/theme/imtpn/pix/logos/logo-imt-white.png");
        }

        return $compactlogourl;
    }

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
     * Renders a custom menu object (located in outputcomponents.php)
     *
     * The custom menu this method produces makes use of the YUI3 menunav widget
     * and requires very specific html elements and classes.
     *
     * @staticvar int $menucount
     * @param custom_menu $menu
     * @return string
     */
    protected function render_custom_menu(custom_menu $menu) {
        global $CFG, $USER;

        $this->add_if_not_exist($menu, new moodle_url('/my'), get_string('mymoodle', 'my'));
        if (mur_pedagogique::has_access($USER->id)) {
            $menu->add(get_string('murpedagogique', 'theme_imtpn'), mur_pedagogique::get_url());
        }
        if (!empty($CFG->enableresourcelibrary)) {
            $this->add_if_not_exist($menu, new moodle_url('/theme/imtpn/pages/themescat.php'),
                get_string('catalogue', 'theme_imtpn'), 'fa fa-star-o text-primary');
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

    protected function add_if_not_exist(custom_menu_advanced $menu, moodle_url $url, $label, $class='') {
        foreach($menu->get_children() as $child) {
            if ($url->out_omit_querystring() == $child->get_url()->out_omit_querystring()) {
                return;
            }
        }
        $menu->add($label, $url, null, null, $class);
    }

    /**
     * Lang menu renderer
     *
     * {@link core_renderer::render_custom_menu()} instead.
     *
     * @param string $custommenuitems - custom menuitems set by theme instead of global theme settings
     * @return string
     */
    public function lang_menu($custommenuitems = '') {
        global $CFG;

        if (empty($CFG->langmenu)) {
            return '';
        }
        if ($this->page->course != SITEID and !empty($this->page->course->lang)) {
            // do not show lang menu if language forced
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
                    null,"flag-icon flag-icon-{$langtype}");
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
     * {@link core_renderer::render_custom_menu()} instead.
     *
     * @param string $custommenuitems - custom menuitems set by theme instead of global theme settings
     * @return string
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
     * The standard tags (typically performance information and validation links,
     * if we are in developer debug mode) that should be output in the footer area
     * of the page. Designed to be called in theme layout.php files.
     *
     * @return string HTML fragment.
     */
    public function standard_footer_html() {
        global $CFG, $SCRIPT;

        $list = [];
        $output = '';
        if (during_initial_install()) {
            // Debugging info can not work before install is finished,
            // in any case we do not want any links during installation!
            return '';
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
            $list[]= html_writer::div(
                $this->render_from_template('core/userfeedback_footer_link', ['url' => core_userfeedback::make_link()->out(false)])
            );
        }

        // This function is normally called from a layout.php file in {@link core_renderer::header()}
        // but some of the content won't be known until later, so we return a placeholder
        // for now. This will be replaced with the real content in {@link core_renderer::footer()}.
        $output .= $this->unique_performance_info_token;
        if ($this->page->devicetypeinuse == 'legacy') {
            // The legacy theme is in use print the notification
            $list[] = html_writer::tag('div', get_string('legacythemeinuse'), array('class'=>'legacythemeinuse'));
        }

        // Get links to switch device types (only shown for users not on a default device)
        $list[] = $this->theme_switch_links();

        if (!empty($CFG->debugpageinfo)) {
            $list[] = '<div class="performanceinfo pageinfo">' . get_string('pageinfodebugsummary', 'core_admin',
                    $this->page->debug_summary()) . '</div>';
        }
        if (debugging(null, DEBUG_DEVELOPER) and has_capability('moodle/site:config', context_system::instance())) {  // Only in developer mode
            // Add link to profiling report if necessary
            if (function_exists('profiling_is_running') && profiling_is_running()) {
                $txt = get_string('profiledscript', 'admin');
                $title = get_string('profiledscriptview', 'admin');
                $url = $CFG->wwwroot . '/admin/tool/profiling/index.php?script=' . urlencode($SCRIPT);
                $link= '<a title="' . $title . '" href="' . $url . '">' . $txt . '</a>';
                $list[]  = '<div class="profilingfooter">' . $link . '</div>';
            }
            $purgeurl = new moodle_url('/admin/purgecaches.php', array('confirm' => 1,
                'sesskey' => sesskey(), 'returnurl' => $this->page->url->out_as_local_url(false)));
            $list[] = '<div class="purgecaches">' .
                html_writer::link($purgeurl, get_string('purgecaches', 'admin')) . '</div>';
        }
        if (!empty($CFG->debugvalidators)) {
            // NOTE: this is not a nice hack, $this->page->url is not always accurate and
            // $FULLME neither, it is not a bug if it fails. --skodak.
            $list[] = '<div class="validators"><ul class="list-unstyled ml-1">
              <li><a href="http://validator.w3.org/check?verbose=1&amp;ss=1&amp;uri=' . urlencode(qualified_me()) . '">Validate HTML</a></li>
              <li><a href="http://www.contentquality.com/mynewtester/cynthia.exe?rptmode=-1&amp;url1=' . urlencode(qualified_me()) . '">Section 508 Check</a></li>
              <li><a href="http://www.contentquality.com/mynewtester/cynthia.exe?rptmode=0&amp;warnp2n3e=1&amp;url1=' . urlencode(qualified_me()) . '">WCAG 1 (2,3) Check</a></li>
            </ul></div>';
        }
        return \html_writer::alist($list). $output;
    }
}