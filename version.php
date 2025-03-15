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
 * @package   local_adler
 * @copyright 2023, Markus Heck <markus.heck@hs-kempten.de>
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2025031505;
$plugin->requires = 2024042200;  // Moodle version
$plugin->component = 'local_adler';
$plugin->release = '6.1.0-dev';
$plugin->maturity = MATURITY_ALPHA;
$plugin->dependencies = array(
    'local_logging' => ANY_VERSION,
    'local_declarativesetup' => '2025031500',
);
