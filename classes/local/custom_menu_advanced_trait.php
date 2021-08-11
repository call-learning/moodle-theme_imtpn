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
 * Custom menu with additional icons (trait)
 *
 * @package   theme_imtpn
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_imtpn\local;

use moodle_url;

/**
 * Trait custom_menu_advanced_trait
 *
 * @package   theme_imtpn
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait custom_menu_advanced_trait {
    /**
     * Adds a custom menu item as a child of this node given its properties.
     *
     * @param string $text
     * @param moodle_url|null $url
     * @param null $title
     * @param null $sort
     * @param null $iconclasses
     * @return custom_menu_item_advanced
     */
    public function add($text, moodle_url $url = null, $title = null, $sort = null, $iconclasses = null) {
        $key = count($this->children);
        if (empty($sort)) {
            $sort = $this->lastsort + 1;
        }
        $this->children[$key] = new custom_menu_item_advanced($text, $url, $title, $sort, $this, $iconclasses);
        $this->lastsort = (int) $sort;
        return $this->children[$key];
    }

    /**
     * Add icon to context for template
     *
     * @param object $context
     */
    protected function add_icon_to_context(&$context) {
        if ($this->iconclasses) {
            $context->iconclasses = $this->iconclasses;
        }
    }
}
