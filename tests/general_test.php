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
 * Generic unit tests
 *
 * Note: This class will be cut into several classes later on.
 *
 * @package   theme_imtpn
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_group_members\output\group_members;
use theme_imtpn\table\groups;

defined('MOODLE_INTERNAL') || die();

/**
 * Class block_group_members
 *
 * @package     theme_imtpn
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class general_testcase extends advanced_testcase {
    /**
     * Group name search
     *
     * @throws coding_exception
     */
    public function test_get_group_member_list() {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $group = [];
        $group[0] =
            $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Statistiques pour l\'ingénieur']);
        $group[1] =
            $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'éthique de la science et ingénieur']);
        list($where, $params) = groups::filter_by_groupname('ingénieur');
        $ingenieur = $DB->get_records_sql("SELECT * FROM {groups} g WHERE 1=1 $where", $params);
        $this->assertCount(2, $ingenieur);
        list($where, $params) = groups::filter_by_groupname('ingenieur');
        $ingenieur = $DB->get_records_sql("SELECT * FROM {groups} g WHERE 1=1 $where", $params);
        $this->assertCount(2, $ingenieur);
        list($where, $params) = groups::filter_by_groupname('ethique');
        $ethique = $DB->get_records_sql("SELECT * FROM {groups} g WHERE 1=1 $where", $params);
        $this->assertCount(1, $ethique);
    }
}