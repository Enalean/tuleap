<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Status;

use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerSemanticStatusFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testImport(): void
    {
        $xml = simplexml_load_string(
            file_get_contents(__DIR__ . '/../../_fixtures/Status/ImportTrackerSemanticStatusTest.xml')
        );

        $all_semantics_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $tracker = TrackerTestBuilder::aTracker()->build();

        $f1 = SelectboxFieldBuilder::aSelectboxField(111)->build();
        $f2 = SelectboxFieldBuilder::aSelectboxField(112)->build();
        $f3 = SelectboxFieldBuilder::aSelectboxField(113)->build();

        $mapping         = [
            'F9'  => $f1,
            'F14' => $f3,
            'F13' => $f2,
            'F14-V61' => 801,
            'F14-V62' => 802,
            'F14-V63' => 803,
            'F14-V64' => 804,
            'F14-V65' => 805,
            'F14-V66' => 806,
            'F14-V67' => 807,
            'F14-V68' => 808,
            'F14-V69' => 809,
        ];
        $semantic_status = TrackerSemanticStatusFactory::instance()->getInstanceFromXML(
            $xml,
            $all_semantics_xml,
            $mapping,
            $tracker,
            []
        );

        $this->assertEquals('status', $semantic_status->getShortName());
        $this->assertEquals(113, $semantic_status->getFieldId());
        $this->assertEquals(4, count($semantic_status->getOpenValues()));
        $this->assertTrue(in_array(806, $semantic_status->getOpenValues()));
        $this->assertTrue(in_array(809, $semantic_status->getOpenValues()));
        $this->assertTrue(in_array(807, $semantic_status->getOpenValues()));
        $this->assertTrue(in_array(808, $semantic_status->getOpenValues()));
    }
}
