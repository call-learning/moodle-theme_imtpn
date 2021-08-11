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
 * Group overview form
 *
 * @package   theme_imtpn
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_imtpn\local\forms;

defined('MOODLE_INTERNAL') || die;

// The form.
global $CFG;
require_once($CFG->libdir . '/formslib.php');
use moodleform;

/**
 * Class groupoverview_form
 *
 * @package   theme_imtpn
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class groupoverview_form extends moodleform {
    /**
     *  constructor.
     *
     * @param null $action
     * @param null $customdata
     * @param string $method
     * @param string $target
     * @param null $attributes
     * @param bool $editable
     * @param null $ajaxformdata
     */
    public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null,
        $editable = true,
        $ajaxformdata = null) {
        parent::__construct($action, $customdata, $method, $target, ['class' => 'groupoverview-search-form container d-flex'],
            $editable, $ajaxformdata);
    }

    /**
     * Definition
     */
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement(
            'text', 'groupname', get_string('groupname', 'theme_imtpn'),
            ['class' => 'container']
        );
        $mform->setType('groupname', PARAM_TEXT);

        $mform->addElement('submit', 'submitbutton', get_string('search'));
        $mform->addElement('cancel', 'cancelbutton', get_string('clear'),
            ['class' => 'mr-auto']);
    }
}
