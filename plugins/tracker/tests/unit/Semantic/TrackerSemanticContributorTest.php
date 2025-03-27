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

use SimpleXMLElement;
use Tracker_Semantic_Contributor;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerSemanticContributorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Tracker_Semantic_Contributor $semantic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semantic = new Tracker_Semantic_Contributor(
            TrackerTestBuilder::aTracker()->build(),
            ListFieldBuilder::aListField(102)->build(),
        );
    }

    public function testExport(): void
    {
        $xml           = simplexml_load_string(file_get_contents(__DIR__ . '/../_fixtures/ImportTrackerSemanticContributorTest.xml'));
        $root          = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_mapping = ['F13' => '102'];

        $this->semantic->exportToXML($root, $array_mapping);

        $this->assertEquals((string) $xml->shortname, (string) $root->semantic->shortname);
        $this->assertEquals((string) $xml->label, (string) $root->semantic->label);
        $this->assertEquals((string) $xml->description, (string) $root->semantic->description);
        $this->assertEquals((string) $xml->field['REF'], (string) $root->semantic->field['REF']);
    }

    public function testItDoesNotExportIfFieldIsNotExported(): void
    {
        $root              = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_xml_mapping = [];

        $this->semantic->exportToXML($root, $array_xml_mapping);

        $this->assertEquals($root->count(), 0);
    }
}
