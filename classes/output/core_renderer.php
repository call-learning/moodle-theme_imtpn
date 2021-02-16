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

use custom_menu;
use moodle_url;
use single_select;
use theme_imtpn\local\custom_menu_with_icon;

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
        global $CFG;

        $langs = get_string_manager()->get_list_of_translations();
        $haslangmenu = $this->lang_menu() != '';

        if (!$menu->has_children() && !$haslangmenu) {
            return '';
        }

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
        $custommenu = new custom_menu_with_icon($custommenuitems, current_language());
        return $this->render_custom_menu($custommenu);
    }
}