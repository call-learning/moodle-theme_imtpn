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
 * CLI script allowing to backup page and attached blocks
 *
 *
 * @package   theme_imtpn
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Get the cli options.
list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'name' => null,
], [
    'h' => 'help'
]);

$usage = "Run different setup script for testing purpose

Usage:
    # php setups.php --name=<functionname>
    # php setups.php [--help|-h]

Options:
    -h --help                   Print this help.
    --name=<frankenstyle>       Name of the function to test/run
";

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL . '  ', $unrecognised);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognised));
}

if ($options['help']) {
    cli_writeln($usage);
    exit(2);
}

$setupclass = new ReflectionClass('\theme_imtpn\setup');

$refpossiblefunctions = $setupclass->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC);
$possiblefunctions = array_map(function($f) {
    return $f->name;
}, $refpossiblefunctions);

if ($options['name'] === null) {
    $options['name'] = 'install_update';
}

if (in_array($options['name'], $possiblefunctions)) {
    $refcalll = $refpossiblefunctions[array_search($options['name'], $possiblefunctions)];
    $refcalll->invoke(null);
} else {
    print ('Called function not in the list (' . implode(',', $possiblefunctions) . ')');
}
