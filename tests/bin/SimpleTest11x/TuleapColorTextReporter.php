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

namespace Tuleap\Test;

/**
 * We need to override reporter to avoid output that conflict with headers (CookieTests)
 *
 * @package Tuleap\Test
 */
class TuleapColorTextReporter extends \ColorTextReporter
{
    private $dots;

    public function paintHeader($test_name)
    {
        if (! \SimpleReporter::inCli()) {
            header('Content-type: text/plain');
        }
        fwrite(STDOUT, "$test_name\n");
        flush();
    }

    public function paintFooter($test_name)
    {
        fwrite(STDOUT, "\n");
        parent::paintFooter($test_name);
    }

    public function paintCaseStart($case)
    {
        parent::paintCaseStart($case);

        fwrite(STDOUT, '.');
        $this->dots++;
        if (($this->dots % 79) === 0) {
            fwrite(STDOUT, "\n");
        }
    }
}
