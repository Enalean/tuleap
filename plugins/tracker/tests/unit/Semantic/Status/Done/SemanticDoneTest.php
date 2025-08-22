<?php
/**
 * Copyright Enalean (c) 2017 - present. All rights reserved.
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

namespace Tuleap\Tracker\Semantic\Status\Done;

use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SemanticDoneTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SemanticDoneValueChecker&MockObject $value_checker;
    private SemanticDoneDao&MockObject $dao;
    private \Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus&MockObject $semantic_status;
    private Tracker $tracker;
    private Tracker_FormElement_Field_List_Bind_StaticValue $done_value;
    private Tracker_FormElement_Field_List_Bind_StaticValue $on_going_value;
    private Tracker_FormElement_Field_List_Bind_StaticValue $to_do_value;

    protected function setUp(): void
    {
        $this->to_do_value    = ListStaticValueBuilder::aStaticValue('todo')->withId(1)->build();
        $this->on_going_value = ListStaticValueBuilder::aStaticValue('on-going')->withId(2)->build();
        $this->done_value     =            ListStaticValueBuilder::aStaticValue('done')->withId(3)->build();

        $this->tracker         = TrackerTestBuilder::aTracker()->build();
        $this->semantic_status = $this->createMock(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus::class);
        $this->dao             = $this->createMock(SemanticDoneDao::class);
        $this->value_checker   = $this->createMock(SemanticDoneValueChecker::class);
    }

    public function testItExportsTheSemanticInXml(): void
    {
        $this->semantic_status->method('getOpenValues')->willReturn([
            1,
            2,
        ]);

        $field = $this->createMock(\Tuleap\Tracker\FormElement\Field\ListField::class);

        $field->method('getId')->willReturn(101);
        $field->method('getAllVisibleValues')->willReturn([
            1 => $this->to_do_value,
            2 => $this->on_going_value,
            3 => $this->done_value,
        ]);

        $this->semantic_status->method('getField')->willReturn($field);

        $xml               = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_xml_mapping = [
            'F14' => 101,
            'values' => [
                'F14-V66' => 1,
                'F14-V67' => 2,
                'F14-V68' => 3,
            ],
        ];

        $semantic_done = new SemanticDone(
            $this->tracker,
            $this->semantic_status,
            $this->dao,
            $this->value_checker,
            [3 => $this->done_value]
        );

        $semantic_done->exportToXML($xml, $array_xml_mapping);

        $this->assertEquals('done', (string) $xml->semantic->shortname);
        $this->assertEquals('Done', (string) $xml->semantic->label);
        $this->assertEquals('F14-V68', (string) $xml->semantic->closed_values->closed_value[0]['REF']);
    }

    public function testItExportsNothingIfNoSemanticStatusDefined(): void
    {
        $this->semantic_status->method('getField')->willReturn(null);

        $xml               = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_xml_mapping = [
            'F14' => 101,
            'values' => [
                'F14-V66' => 1,
                'F14-V67' => 2,
                'F14-V68' => 3,
            ],
        ];

        $semantic_done = new SemanticDone(
            $this->tracker,
            $this->semantic_status,
            $this->dao,
            $this->value_checker,
            []
        );

        $semantic_done->exportToXML($xml, $array_xml_mapping);

        $this->assertEquals('', (string) $xml->semantic);
    }
}
