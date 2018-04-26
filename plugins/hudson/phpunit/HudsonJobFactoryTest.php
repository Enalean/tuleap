<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved.
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
 */

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;

class HudsonJobFactoryTest extends TestCase // @codingStandardsIgnoreLine
{
    protected function setUp()
    {
        $GLOBALS['Language'] = Mockery::spy(BaseLanguage::class);
    }

    protected function tearDown()
    {
        unset($GLOBALS['Language']);
    }

    /**
     * @expectedException HudsonJobURLMalformedException
     */
    public function testBuildingJobWithMalformedURL()
    {
        $job_factory = new MinimalHudsonJobFactory();
        $job_factory->getMinimalHudsonJob('toto', '');
    }

    /**
     * @expectedException HudsonJobURLMalformedException
     */
    public function testBuildingJobWithMissingSchemeURL()
    {
        $job_factory = new MinimalHudsonJobFactory();
        $job_factory->getMinimalHudsonJob('example.com/hudson/jobs/Tuleap', '');
    }

    /**
     * @expectedException HudsonJobURLMalformedException
     */
    public function testBuildingJobWithMissingHostURL()
    {
        $job_factory = new MinimalHudsonJobFactory();
        $job_factory->getMinimalHudsonJob('https://', '');
    }
}
