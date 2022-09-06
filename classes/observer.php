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
namespace theme_imtpn;

use core\event\user_loggedin;
use core\event\user_loggedinas;

/**
 * Observer class for different events
 *
 * @package   theme_imtpn
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * When user has logged in we check if the user theme is right
     *
     * @param user_loggedin $event
     * @return void
     */
    public static function user_has_logged_in(user_loggedin $event) {
        setup::setup_user_theme($event->userid);
    }
    /**
     * When user has logged in as we also check the theme
     *
     * @param user_loggedinas $event
     * @return void
     */
    public static function user_has_logged_in_as(user_loggedinas $event) {
        setup::setup_user_theme($event->relateduserid);
    }
}
