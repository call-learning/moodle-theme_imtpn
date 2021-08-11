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
 * Mur pedagogique group enrolment form (with key)
 *
 * @package   theme_imtpn
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_imtpn\form;
defined('MOODLE_INTERNAL') || die();

use moodleform;

global $CFG;
require_once($CFG->dirroot . '/lib/formslib.php');

/**
 * Group enrolment form class
 *
 * @package   theme_imtpn
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_enrolment_form extends moodleform {

    /**
     * Form definition
     */
    protected function definition() {
        $mform =& $this->_form;
        $mform->addElement('passwordunmask', 'enrolmentkey', get_string('enrolmentkey', 'group'), 'maxlength="254" size="24"',
            get_string('enrolmentkey', 'group'));
        $mform->addHelpButton('enrolmentkey', 'enrolmentkey', 'group');
        $mform->setType('enrolmentkey', PARAM_RAW);

        $mform->addElement('hidden', 'groupid');
        $mform->setType('groupid', PARAM_INT);

        $this->add_action_buttons();
    }
}
