<?php
/**
 * Copyright (c) Enalean, 2015 - present. All rights reserved
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

use SimpleXMLElement;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerSemanticTitleTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SimpleXMLElement $xml;
    private TrackerSemanticTitle $semantic_title;
    private SimpleXMLElement $root;

    public function setUp(): void
    {
        $this->xml = simplexml_load_string(
            file_get_contents(__DIR__ . '/../../_fixtures/ImportTrackerSemanticTitleTest.xml')
        );

        $tracker = TrackerTestBuilder::aTracker()->build();
        $field   = TextFieldBuilder::aTextField(102)->build();

        $this->semantic_title = new TrackerSemanticTitle($tracker, $field);
        $this->root           = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
    }

    public function testExport(): void
    {
        $array_mapping = ['F13' => '102'];
        $this->semantic_title->exportToXML($this->root, $array_mapping);

        $this->assertEquals((string) $this->xml->shortname, (string) $this->root->semantic->shortname);
        $this->assertEquals((string) $this->xml->label, (string) $this->root->semantic->label);
        $this->assertEquals((string) $this->xml->description, (string) $this->root->semantic->description);
        $this->assertEquals((string) $this->xml->field['REF'], (string) $this->root->semantic->field['REF']);
    }

    public function testItDoesntExportTheFieldIfNotDefinedInMapping(): void
    {
        $this->semantic_title->exportToXML($this->root, []);

        $this->assertEquals(0, count($this->root->children()));
    }
}
