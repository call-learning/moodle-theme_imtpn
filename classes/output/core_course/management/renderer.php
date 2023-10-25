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
 * This file contains overrides for the renderers for the course.
 *
 * @copyright 2023 Bas Brands
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package theme_imtpn
 */

namespace theme_imtpn\output\core_course\management;

use stdClass;
use html_writer;
use action_menu;
use action_menu_link;
use pix_icon;
use lang_string;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/resourcelibrary/lib.php');

/**
 * Add the favourite icon to courses and categories.
 *
 * @copyright 2023 Bas Brands
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package theme_imtpn
 */
class renderer extends \core_course_management_renderer {

    /**
     * Initialises the JS required to enhance the management interface.
     *
     */
    public function enhance_management_interface() {
        parent::enhance_management_interface();

        // Call the external function to set the visibility of the course.
        $this->page->requires->js_call_amd('local_resourcelibrary/item_visibility', 'init');
    }
    /**
     * Renderers the actions for individual category list items.
     *
     * @param core_course_category $category
     * @param array $actions
     * @return string
     */
    public function category_listitem_actions(\core_course_category $category, array $actions = null) {
        global $DB;
        if ($actions === null) {
            $actions = \core_course\management\helper::get_category_listitem_actions($category);
        }

        $menu = new action_menu();
        $menu->attributes['class'] .= ' category-item-actions item-actions';
        $hasitems = false;
        // Check if the category is visible in the catalogue.
        $sql = "SELECT id, visibility FROM {local_resourcelibrary} WHERE itemid = :categoryid and itemtype = 1";
        $params = ['categoryid' => $category->id];
        $rlrecord = $DB->get_record_sql($sql, $params);
        $lrrecordid = 0;
        $visibility = LOCAL_RESOURCELIBRARY_ITEM_VISIBLE;
        if ($rlrecord) {
            $visibility = $rlrecord->visibility;
            $lrrecordid = $rlrecord->id;
        }
        $ishidden = ($visibility == LOCAL_RESOURCELIBRARY_ITEM_HIDDEN);

        $menu->add(new action_menu_link(
            new moodle_url('/'),
            new pix_icon('i/star-o', new lang_string('showincatalogue', 'local_resourcelibrary')),
            get_string('showincatalogue', 'local_resourcelibrary'),
            true,
            [
                'data-id' => $lrrecordid,
                'data-action' => 'showincatalogue',
                'data-itemtype' => LOCAL_RESOURCELIBRARY_ITEMTYPE_CATEGORY,
                'data-itemid' => $category->id,
                'class' => $ishidden ? 'action-showincatalogue' : 'd-none action-showincatalogue'
            ]
        ));

        $menu->add(new action_menu_link(
            new moodle_url('/'),
            new pix_icon('i/star', new lang_string('hidefromcatalogue', 'local_resourcelibrary')),
            get_string('hidefromcatalogue', 'local_resourcelibrary'),
            true,
                [
                    'data-id' => $lrrecordid,
                    'data-action' => 'hidefromcatalogue',
                    'data-itemtype' => LOCAL_RESOURCELIBRARY_ITEMTYPE_CATEGORY,
                    'data-itemid' => $category->id,
                    'class' => $ishidden ? 'd-none action-hidefromcatalogue' : 'action-hidefromcatalogue'
                ]
        ));

        foreach ($actions as $key => $action) {
            $hasitems = true;
            $menu->add(new action_menu_link(
                $action['url'],
                $action['icon'],
                $action['string'],
                in_array($key, array('show', 'hide', 'moveup', 'movedown')),
                array('data-action' => $key, 'data-itemid' => $category->id, 'class' => 'action-'.$key)
            ));
        }

        if (!$hasitems) {
            return '';
        }

        // If the action menu has items, add the menubar role to the main element containing it.
        $menu->attributes['role'] = 'menubar';

        return $this->render($menu);
    }

    /**
     * Renderers actions for individual course actions.
     *
     * @param core_course_category $category The currently selected category.
     * @param core_course_list_element  $course The course to renderer actions for.
     * @return string
     */
    public function course_listitem_actions(\core_course_category $category, \core_course_list_element $course) {
        global $DB;
        // Check if the course is visible in the catalogue.
        $sql = "SELECT id, visibility FROM {local_resourcelibrary} WHERE itemid = :courseid and itemtype = 2";
        $params = ['courseid' => $course->id];
        $rlrecord = $DB->get_record_sql($sql, $params);
        $lrrecordid = 0;
        $visibility = LOCAL_RESOURCELIBRARY_ITEM_VISIBLE;
        if ($rlrecord) {
            $visibility = $rlrecord->visibility;
            $lrrecordid = $rlrecord->id;
        }
        $ishidden = ($visibility == LOCAL_RESOURCELIBRARY_ITEM_HIDDEN);

        $actions = \core_course\management\helper::get_course_listitem_actions($category, $course);
        if (empty($actions)) {
            return '';
        }
        $actionshtml = array();
        $showincatalogueaction = [
            'url' => new moodle_url('/'),
            'icon' => new pix_icon('i/star-o', new lang_string('showincatalogue', 'local_resourcelibrary')),
            'attributes' => [
                'data-id' => $lrrecordid,
                'data-action' => 'showincatalogue',
                'data-itemtype' => LOCAL_RESOURCELIBRARY_ITEMTYPE_COURSE,
                'data-itemid' => $course->id,
                'class' => $ishidden ? 'action-showincatalogue' : 'd-none action-showincatalogue'
            ]
        ];
        $hidefromcatalogueaction = [
            'url' => new moodle_url('/'),
            'icon' => new pix_icon('i/star', new lang_string('hidefromcatalogue', 'local_resourcelibrary')),
            'attributes' => [
                'data-id' => $lrrecordid,
                'data-action' => 'hidefromcatalogue',
                'data-itemtype' => LOCAL_RESOURCELIBRARY_ITEMTYPE_COURSE,
                'data-itemid' => $course->id,
                'class' => $ishidden ? 'd-none action-hidefromcatalogue' : 'action-hidefromcatalogue'
            ]
        ];
        // Inject the show/hide actions at the start of the array.
        array_unshift($actions, $showincatalogueaction, $hidefromcatalogueaction);
        foreach ($actions as $action) {
            $action['attributes']['role'] = 'button';
            $actionshtml[] = $this->output->action_icon($action['url'], $action['icon'], null, $action['attributes']);
        }
        return html_writer::span(join('', $actionshtml), 'course-item-actions item-actions mr-0');
    }
}
