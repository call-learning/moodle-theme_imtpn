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
 * Local plugin envf - Upgrade plugin tasks
 *
 * @package   theme_imtpn
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for this plugin
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool
 * @throws ddl_exception
 * @throws ddl_table_missing_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_theme_imtpn_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2021011900) {
        theme_imtpn\setup::install_update();
        upgrade_plugin_savepoint(true, 2021011900, 'theme', 'imtpn');
    }
    if ($oldversion < 2021011914) {
        theme_imtpn\setup::install_update();
        upgrade_plugin_savepoint(true, 2021011914, 'theme', 'imtpn');
    }
    if ($oldversion < 2021011915) {
        theme_imtpn\setup::install_update();
        upgrade_plugin_savepoint(true, 2021011915, 'theme', 'imtpn');
    }
    return true;
}