<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Tracker\Semantic;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_TooltipFactory;

class Tracker_TooltipFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    //testing Tooltip import
    public function testImport()
    {
        $xml = simplexml_load_string(
            file_get_contents(__DIR__ . '/../../_fixtures/ImportTrackerSemanticTooltipTest.xml')
        );
        $tracker = Mockery::mock(Tracker::class);

        $mapping = [
                    'F8'  => 108,
                    'F9'  => 109,
                    'F16' => 116,
                    'F14' => 114
        ];
        $tooltip = Tracker_TooltipFactory::instance()->getInstanceFromXML($xml, $mapping, $tracker);

        $this->assertEquals(3, count($tooltip->getFields()));
        $fields = $tooltip->getFields();
        $this->assertTrue(in_array(108, $fields));
        $this->assertTrue(in_array(109, $fields));
        $this->assertTrue(in_array(116, $fields));
        $this->assertFalse(in_array(114, $fields));
    }
}
