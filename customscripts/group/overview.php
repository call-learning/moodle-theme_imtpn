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
 * Group overview
 *
 * Override this
 * @copyright 2006 The Open University, N.D.Freear AT open.ac.uk, J.White AT open.ac.uk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   core_group
 */
require_once('lib.php');

$courseid   = required_param('id', PARAM_INT);
$cm = \theme_imtpn\mur_pedagogique::get_cm();
if ($courseid == $cm->course) {
    $groupid = required_param('groupid', PARAM_INT);
    redirect(new moodle_url('/theme/imtpn/pages/murpedagogique/grouppage.php', array('groupid'=> $groupid)));
    die();
}