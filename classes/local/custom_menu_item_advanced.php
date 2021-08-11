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
 * Custom menu with additional icons
 *
 * @package   theme_imtpn
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_imtpn\local;

use custom_menu_item;
use moodle_url;
use renderer_base;

/**
 * Custom menu class with additional data
 *
 * @package   theme_imtpn
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_menu_item_advanced extends custom_menu_item {

    use custom_menu_advanced_trait;

    /**
     * @var mixed|null $iconclasses class for icon
     */
    protected $iconclasses;

    /**
     * @var bool $iscurrentpage is current page or not
     */
    protected $iscurrentpage = false;

    /**
     * Constructs the new custom menu item
     *
     * @param string $text
     * @param moodle_url|null $url A moodle url to apply as the link for this item [Optional]
     * @param null $title A title to apply to this item [Optional]
     * @param null $sort A sort or to use if we need to sort differently [Optional]
     * @param custom_menu_item|null $parent A reference to the parent custom_menu_item this child
     *        belongs to, only if the child has a parent. [Optional]
     * @param null $iconclasses
     */
    public function __construct($text, moodle_url $url = null, $title = null, $sort = null, custom_menu_item $parent = null,
        $iconclasses = null) {
        global $PAGE;
        parent::__construct($text, $url, $title, $sort, $parent);
        $this->iconclasses = $iconclasses;
        if ($url->out_omit_querystring() == $PAGE->url->out_omit_querystring()) {
            $this->iscurrentpage = true;
        }
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $context = parent::export_for_template($output);
        $this->add_icon_to_context($context);
        $context->additionalclasses = $this->iscurrentpage ? 'currentpage' : '';
        return $context;
    }
}
