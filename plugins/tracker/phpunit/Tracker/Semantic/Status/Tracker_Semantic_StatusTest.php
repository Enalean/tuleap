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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field_List;
use Tracker_Semantic_Status;
use Tuleap\GlobalLanguageMock;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_Semantic_StatusTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $tracker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElement_Field_List
     */
    private $field;

    public function setUp(): void
    {
        $this->tracker = Mockery::mock(Tracker::class);
        $this->field   = Mockery::mock(Tracker_FormElement_Field_List::class);
        $this->field->shouldReceive('getId')->andReturn(103);

        $GLOBALS['Language']->shouldReceive('getText')->with(
            'plugin_tracker_admin_semantic',
            'status_label'
        )->andReturns('Status');
        $GLOBALS['Language']->shouldReceive('getText')->with(
            'plugin_tracker_admin_semantic',
            'status_description'
        )->andReturns('Define the status of an artifact');
    }

    public function testExport()
    {
        $xml = simplexml_load_string(
            file_get_contents(__DIR__ . '/../../_fixtures/Status/ImportTrackerSemanticStatusTest.xml')
        );
        $semantic = new Tracker_Semantic_Status($this->tracker, $this->field, [806, 807, 808, 809]);
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $array_xml_mapping = [
            'F14' => 103,
            'values' => [
                'F14-V66' => 806,
                'F14-V67' => 807,
                'F14-V68' => 808,
                'F14-V69' => 809,
            ]
        ];

        $semantic->exportToXML($root, $array_xml_mapping);

        $this->assertEquals((string) $xml->shortname, (string) $root->semantic->shortname);
        $this->assertEquals((string) $xml->label, (string) $root->semantic->label);
        $this->assertEquals((string) $xml->description, (string) $root->semantic->description);
        $this->assertEquals((string) $xml->field['REF'], (string) $root->semantic->field['REF']);
        $this->assertEquals(count($xml->open_values), count($root->semantic->open_values));
    }

    public function testItDoesNotExportIfFieldIsNotExported()
    {
        $semantic = new Tracker_Semantic_Status($this->tracker, $this->field);
        $root     = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $array_xml_mapping = [];

        $semantic->exportToXML($root, $array_xml_mapping);

        $this->assertEquals(0, $root->count());
    }
}
