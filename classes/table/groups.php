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
use core_table\dynamic as dynamic_table;
use core_table\local\filter\filterset;
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
class groups extends \table_sql implements dynamic_table {
    /**
     * @var int $courseid The course id
     */
    protected $courseid;
    /**
     * @var \stdClass $course The course details.
     */
    protected $course;

    /**
     * @var  context $context The course context.
     */
    protected $context;

    public function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $this->sql = new \stdClass();
        $cols = [
            'groupid'=> get_string('groups:groupid', 'theme_imtpn'),
            'groupimage'=> get_string('groups:groupimage', 'theme_imtpn'),
            'members'=> get_string('groups:members', 'theme_imtpn'),
            'postcount'=> get_string('groups:postcount', 'theme_imtpn'),
            'grouplink'=> get_string('groups:grouplink', 'theme_imtpn')
        ];

        $this->define_columns(array_keys($cols));
        $this->define_headers(array_values($cols));
        $this->collapsible(false);
        $this->sortable(false);
        $this->pageable(true);
        $this->sql->fields = "g.id AS groupid,g.name AS groupname";
        $this->sql->from = "FROM {groups} g";
        $this->sql->where = "g.courseid = :courseid";
        $this->sql->params = [];
    }

    /**
     * Set filters and build table structure.
     *
     * @param filterset $filterset The filterset object to get the filters from.
     * @throws \dml_exception
     */
    public function set_filterset(filterset $filterset): void {
        // Get the context.
        $this->courseid = $filterset->get_filter('courseid')->current();
        $this->course = get_course($this->courseid);
        $this->context = \context_course::instance($this->courseid, MUST_EXIST);

        // Process the filterset.
        parent::set_filterset($filterset);
        $this->sql->params = ['courseid' => $this->courseid];
    }
}