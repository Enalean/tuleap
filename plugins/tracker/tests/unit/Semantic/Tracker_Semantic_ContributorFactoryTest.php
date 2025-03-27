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

namespace Tuleap\Tracker\Semantic;

use Tracker_Semantic_ContributorFactory;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Semantic_ContributorFactoryTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function testImport(): void
    {
        $xml = simplexml_load_string(
            file_get_contents(__DIR__ . '/../_fixtures/ImportTrackerSemanticContributorTest.xml')
        );

        $all_semantics_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $tracker = TrackerTestBuilder::aTracker()->build();

        $f1 = ListFieldBuilder::aListField(111)->build();
        $f2 = ListFieldBuilder::aListField(112)->build();
        $f3 = ListFieldBuilder::aListField(113)->build();

        $mapping              = [
            'F9'  => $f1,
            'F13'  => $f2,
            'F16' => $f3,
        ];
        $semantic_contributor = Tracker_Semantic_ContributorFactory::instance()->getInstanceFromXML(
            $xml,
            $all_semantics_xml,
            $mapping,
            $tracker,
            []
        );

        $this->assertEquals('contributor', $semantic_contributor->getShortName());
        $this->assertEquals(112, $semantic_contributor->getFieldId());
    }
}
