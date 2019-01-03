<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap;

use PHPUnit\Framework\TestCase;

class LanguageFilesTest extends TestCase
{
    public function testLanguagesFiles()
    {
        $cmd          = __DIR__.'/../../src/utils/analyse_language_files.pl '.__DIR__.'/../../'.' 2>&1';
        $return_value = 1;
        $output       = array();
        exec($cmd, $output, $return_value);
        $full_output = implode("\n", $output);
        if ($return_value != 0 || preg_match('/[1-9]\s*(missing|incorrect|duplicate) keys/', $full_output)) {
            echo "<pre>\n$full_output\n</pre>";
            self::fail();
        } else {
            $this->assertTrue(true);
        }
    }
}
