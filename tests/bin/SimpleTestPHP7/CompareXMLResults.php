<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

class CompareXMLResults
{
    public function main(array $argv)
    {
        $php70 = simplexml_load_file($argv[1]);
        $php56 = simplexml_load_file($argv[2]);

        if ((int) $php70['tests'] === (int) $php56['tests'] &&
        (int) $php70['failures'] === (int) $php56['failures'] &&
        (int) $php70['errors'] === (int) $php56['errors']) {
            echo "PHP 7.0 and PHP 5.6 test suites are equal\n";
            echo "There are {$php70['tests']} tests ready for PHP 7.0\n";
            exit(0);
        } else {
            echo "*** ERROR: PHP 7.0 and PHP 5.6 test suites differs\n";
            exit(1);
        }
    }
}
