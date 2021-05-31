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
 * Contains the class used for the displaying the participants table.
 *
 * @package   theme_imtpn
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
declare(strict_types=1);

namespace theme_imtpn\table;

use context;
use context_course;
use core_renderer;
use core_table\dynamic as dynamic_table;
use core_table\local\filter\filterset;
use dml_exception;
use html_writer;
use moodle_url;
use stdClass;
use table_sql;
use theme_imtpn\mur_pedagogique;
use user_picture;

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->libdir . '/tablelib.php');

/**
 * Class for the displaying a course group table.
 *
 * @package   theme_imtpn
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class groups extends table_sql implements dynamic_table {
    const MEMBER_DISPLAY_LIMIT = 5;
    /**
     * @var int $courseid The course id
     */
    protected $courseid;
    /**
     * @var stdClass $course The course details.
     */
    protected $course;
    /**
     * @var  context $context The course context.
     */
    protected $context;

    public function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $this->sql = new stdClass();
        $cols = [
            'groupimage' => '',
            'groupname' => get_string('groups:groupname', 'theme_imtpn'),
            'members' => get_string('groups:members', 'theme_imtpn'),
            'postcount' => get_string('groups:postcount', 'theme_imtpn'),
            'grouplink' => ''
        ];

        $this->define_columns(array_keys($cols));
        $this->define_headers(array_values($cols));
        $this->collapsible(false);
        $this->sortable(true);
        $this->no_sorting('members');
        $this->no_sorting('grouplink');
        $this->no_sorting('groupimage');
        $this->pageable(true);
        $forumcondition = '';
        $cm = mur_pedagogique::get_cm();
        if ($cm) {
            $forumcondition = " WHERE d.forum = {$cm->instance}";
        }
        $this->sql->fields = "g.id AS groupid,g.name AS groupname, COALESCE(dc.count,0) as postcount";
        $this->sql->from = " {groups} g 
            LEFT JOIN (SELECT COUNT(*) count, d.groupid FROM {forum_discussions} d 
            LEFT JOIN {forum_posts} p ON p.discussion = d.id $forumcondition  GROUP BY d.groupid) dc ON g.id = dc.groupid ";
        $this->sql->where = "g.courseid = :courseid";
        $this->sql->params = [];
    }

    /**
     * Set filters and build table structure.
     *
     * @param filterset $filterset The filterset object to get the filters from.
     * @throws dml_exception
     */
    public function set_filterset(filterset $filterset): void {
        global $DB;
        // Get the context.
        $this->courseid = $filterset->get_filter('courseid')->current();
        $this->course = get_course($this->courseid);
        $this->context = context_course::instance($this->courseid, MUST_EXIST);

        // Process the filterset.
        parent::set_filterset($filterset);
        $this->sql->params = ['courseid' => $this->courseid];
        if ($filterset->has_filter('name')) {
            $groupname = $filterset->get_filter('name')->current();
            if (!empty($groupname)) {
                $this->sql->where .= ' AND ' . $DB->sql_like('g.name', ':groupname', false, false);
                $this->sql->params['groupname'] = "%{$groupname}%";
            }
        }
    }

    public function col_name($row) {
        return format_string($row->groupname, true, ['context' => $this->get_context()]);
    }

    /**
     * Get the context of the current table.
     *
     * Note: This function should not be called until after the filterset has been provided.
     *
     * @return context
     */
    public function get_context(): context {
        return $this->context;
    }

    public function col_groupimage($row) {
        $group = groups_get_group($row->groupid, '*', MUST_EXIST);
        return html_writer::img(get_group_picture_url($group, $this->courseid, true),
            $row->groupname
        );
    }

    public function col_members($row) {
        global $OUTPUT;
        $extrafields = get_extra_user_fields($this->get_context());
        $extrafields[] = 'picture';
        $extrafields[] = 'imagealt';
        $allnames = 'u.id, ' . user_picture::fields('u', $extrafields);
        $members = groups_get_members($row->groupid, $allnames);
        $html = '';
        $additionalmessage = '';
        if (count($members) > self::MEMBER_DISPLAY_LIMIT) {
            $members = array_slice($members, 0, self::MEMBER_DISPLAY_LIMIT);
            $additionalmessage = html_writer::span(get_string('andmore', 'theme_imtpn'), 'andmore');
        }
        foreach ($members as $user) {
            /* @var $OUTPUT core_renderer core renderer */
            $html .= html_writer::span($OUTPUT->user_picture($user, ['includefullname' => false]));
        }
        return $html . $additionalmessage;
    }

    public function col_grouplink($row) {
        $group = groups_get_group($row->groupid, '*', MUST_EXIST);
        return html_writer::link(
            mur_pedagogique::get_group_page_url($group),
            html_writer::span('', 'fa fa-arrow-circle-o-right fa-2x')
        );
    }

    /**
     * Guess the base url for the participants table.
     */
    public function guess_base_url(): void {
        $this->baseurl = new moodle_url('/theme/imtpn/pages/murpedagogique/groupoverview.php');
    }
}