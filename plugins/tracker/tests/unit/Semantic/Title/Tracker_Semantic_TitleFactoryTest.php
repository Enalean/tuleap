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

namespace Tuleap\Tracker\Semantic\Title;

use Tracker_Semantic_TitleFactory;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Semantic_TitleFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testImport()
    {
        $xml = simplexml_load_string(
            file_get_contents(__DIR__ . '/../../_fixtures/ImportTrackerSemanticTitleTest.xml')
        );

        $all_semantics_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $tracker = TrackerTestBuilder::aTracker()->build();

        $f1 = TextFieldBuilder::aTextField(111)->build();
        $f2 = TextFieldBuilder::aTextField(112)->build();
        $f3 = TextFieldBuilder::aTextField(113)->build();

        $mapping        = [
            'F9'  => $f1,
            'F13' => $f2,
            'F16' => $f3,
        ];
        $semantic_title = Tracker_Semantic_TitleFactory::instance()->getInstanceFromXML($xml, $all_semantics_xml, $mapping, $tracker, []);

        $this->assertEquals('title', $semantic_title->getShortName());
        $this->assertEquals(112, $semantic_title->getFieldId());
    }
}
