<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Tuleap\Test\PHPUnit\TestCase;

final class HudsonJobFactoryTest extends TestCase // @codingStandardsIgnoreLine
{
    use \Tuleap\GlobalLanguageMock;

    public function testBuildingJobWithMalformedURL(): void
    {
        $job_factory = new MinimalHudsonJobFactory();

        $this->expectException(HudsonJobURLMalformedException::class);

        $job_factory->getMinimalHudsonJob('toto', '');
    }

    public function testBuildingJobWithMissingSchemeURL(): void
    {
        $job_factory = new MinimalHudsonJobFactory();

        $this->expectException(HudsonJobURLMalformedException::class);

        $job_factory->getMinimalHudsonJob('example.com/hudson/jobs/Tuleap', '');
    }

    public function testBuildingJobWithMissingHostURL(): void
    {
        $job_factory = new MinimalHudsonJobFactory();

        $this->expectException(HudsonJobURLMalformedException::class);

        $job_factory->getMinimalHudsonJob('https://', '');
    }
}
