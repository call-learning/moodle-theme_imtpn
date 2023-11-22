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

// If localplugin dashby role is installed, then include $CFG->dirroot.'/local/dash_by_role/customscripts/my/'
// in the $CFG->customscripts array.
$dashbyroleplugin = core_plugin_manager::instance()->get_plugin_info('local_dash_by_role');
if (!empty($dashbyroleplugin)) {
    if ($dashbyroleplugin->is_installed_and_upgraded()) {
        global $CFG;
        include_once($CFG->dirroot . '/local/dash_by_role/customscripts/my/index.php');
    }
}