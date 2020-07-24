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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field_List;
use Tracker_Semantic_Contributor;

class TrackerSemanticContributorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Tracker_FormElement_Field_List|\Mockery\MockInterface|Tracker_FormElement_Field_List
     */
    private $field;

    /**
     * @var Tracker|\Mockery\MockInterface|Tracker
     */
    private $tracker;

    /**
     * @var Tracker_Semantic_Contributor
     */
    private $semantic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tracker = Mockery::mock(\Tracker::class);
        $this->field   = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $this->field->shouldReceive('getId')->andReturn(102);

        $this->semantic = new Tracker_Semantic_Contributor($this->tracker, $this->field);
    }

    public function testExport()
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

    public function testItDoesNotExportIfFieldIsNotExported()
    {
        $root              = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_xml_mapping = [];

        $this->semantic->exportToXML($root, $array_xml_mapping);

        $this->assertEquals($root->count(), 0);
    }
}
