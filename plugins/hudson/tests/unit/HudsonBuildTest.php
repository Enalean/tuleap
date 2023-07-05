<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
 */

namespace Tuleap\Hudson;

use Http\Mock\Client;
use HudsonBuild;
use HudsonJobURLMalformedException;
use Tuleap\GlobalLanguageMock;
use Tuleap\Http\HTTPFactoryBuilder;

final class HudsonBuildTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    public function testMalformedURL(): void
    {
        $this->expectException(HudsonJobURLMalformedException::class);

        new HudsonBuild("toto", new Client(), HTTPFactoryBuilder::requestFactory());
    }

    public function testMissingSchemeURL(): void
    {
        $this->expectException(HudsonJobURLMalformedException::class);

        new HudsonBuild("code4:8080/hudson/jobs/tuleap", new Client(), HTTPFactoryBuilder::requestFactory());
    }

    public function testMissingHostURL(): void
    {
        $this->expectException(HudsonJobURLMalformedException::class);

        new HudsonBuild("http://", new Client(), HTTPFactoryBuilder::requestFactory());
    }

    public function testSimpleJobBuild(): void
    {
        $build_file = __DIR__ . '/resources/jobbuild.xml';
        $xmldom     = simplexml_load_string(file_get_contents($build_file), \SimpleXMLElement::class, LIBXML_NONET);

        $build = new HudsonBuild(
            "http://myCIserver/jobs/myCIjob/lastBuild/",
            new Client(),
            HTTPFactoryBuilder::requestFactory(),
            $xmldom,
        );

        self::assertEquals("freeStyleBuild", $build->getBuildStyle());
        self::assertFalse($build->isBuilding());
        self::assertEquals("http://example.com:8080/hudson/job/tuleap/87/", $build->getUrl());
        self::assertEquals("UNSTABLE", $build->getResult());
        self::assertEquals(87, $build->getNumber());
        self::assertEquals(359231, $build->getDuration());
        self::assertEquals(1230051671000, $build->getTimestamp());
    }
}
