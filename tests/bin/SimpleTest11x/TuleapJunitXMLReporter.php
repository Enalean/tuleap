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

class TuleapJunitXMLReporter extends \JUnitXMLReporter
{
    private $dots;

    public function paintHeader($test_name)
    {
        $this->testsStart = microtime(true);

        $this->root->setAttribute('name', $test_name);
        $this->root->setAttribute('timestamp', date('c'));
        $this->root->setAttribute('hostname', 'localhost');

        fwrite(STDOUT, "Tuleap test suite\n");
    }

    public function paintFooter($test_name)
    {
        $duration = microtime(true) - $this->testsStart;

        $this->root->setAttribute('tests', $this->getPassCount() + $this->getFailCount() + $this->getExceptionCount());
        $this->root->setAttribute('failures', $this->getFailCount());
        $this->root->setAttribute('errors', $this->getExceptionCount());
        $this->root->setAttribute('time', $duration);

        $this->doc->formatOutput = true;

        fwrite(STDOUT, "\n");
    }

    public function paintCaseStart($case)
    {
        fwrite(STDOUT, '.');
        $this->dots++;
        if (($this->dots % 79) === 0) {
            fwrite(STDOUT, "\n");
        }
        $this->currentCaseName = $case;
    }

    public function paintMethodStart($test)
    {
        $this->methodStart = microtime(true);
        $this->currCase = $this->doc->createElement('testcase');
    }
}
