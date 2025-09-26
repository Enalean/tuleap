<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
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
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SemanticDoneValueCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Tracker_FormElement_Field_List_Bind_StaticValue $to_do_value;
    private Tracker_FormElement_Field_List_Bind_StaticValue $on_going_value;
    private Tracker_FormElement_Field_List_Bind_StaticValue $done_value;
    private Tracker_FormElement_Field_List_Bind_StaticValue $hidden_value;
    private Tracker_FormElement_Field_List_Bind_StaticValue $xml_to_do_value;
    private Tracker_FormElement_Field_List_Bind_StaticValue $xml_on_going_value;
    private Tracker_FormElement_Field_List_Bind_StaticValue $xml_done_value;
    private Tracker_FormElement_Field_List_Bind_StaticValue $xml_hidden_value;
    private SimpleXMLElement $xml_semantic_status;
    private SemanticDoneValueChecker $value_checker;
    private TrackerSemanticStatus&MockObject $semantic_status;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->to_do_value    = ListStaticValueBuilder::aStaticValue('todo')->withId(1)->isHidden(false)->build();
        $this->on_going_value = ListStaticValueBuilder::aStaticValue('on-going')->withId(2)->isHidden(false)->build();
        $this->done_value     = ListStaticValueBuilder::aStaticValue('done')->withId(3)->isHidden(false)->build();
        $this->hidden_value   = ListStaticValueBuilder::aStaticValue('hidden')->withId(4)->isHidden(true)->build();

        $this->xml_to_do_value    = ListStaticValueBuilder::aStaticValue('todo')->withXMLId('F1')->isHidden(false)->build();
        $this->xml_on_going_value = ListStaticValueBuilder::aStaticValue('on-going')->withXMLId('F2')->isHidden(false)->build();
        $this->xml_done_value     = ListStaticValueBuilder::aStaticValue('done')->withXMLId('F3')->isHidden(false)->build();
        $this->xml_hidden_value   = ListStaticValueBuilder::aStaticValue('hidden')->withXMLId('F4')->isHidden(true)->build();

        $this->semantic_status = $this->createMock(TrackerSemanticStatus::class);
        $this->semantic_status->method('getOpenValues')->willReturn([
            1,
            2,
        ]);

        $this->xml_semantic_status = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
<semantic type="status">
 <shortname>status</shortname>
 <open_values>
    <open_value REF="F1"/>
    <open_value REF="F2"/>
 </open_values>
</semantic>');

        $this->value_checker = new SemanticDoneValueChecker();
    }

    public function testItReturnsTrueWhenTheValueCouldBeAddedAsADoneValue(): void
    {
        $this->assertTrue($this->value_checker->isValueAPossibleDoneValue($this->done_value, $this->semantic_status));
    }

    public function testItReturnsFalseWhenTheValueIsAnOpenValue(): void
    {
        $this->assertFalse($this->value_checker->isValueAPossibleDoneValue($this->to_do_value, $this->semantic_status));
        $this->assertFalse($this->value_checker->isValueAPossibleDoneValue($this->on_going_value, $this->semantic_status));
    }

    public function testItReturnsFalseWhenTheValueIsHidden(): void
    {
        $this->assertFalse($this->value_checker->isValueAPossibleDoneValue($this->hidden_value, $this->semantic_status));
    }

    public function testItReturnsTrueWhenTheValueCouldBeAddedAsADoneValueInXML(): void
    {
        $this->assertTrue($this->value_checker->isValueAPossibleDoneValueInXMLImport(
            $this->xml_done_value,
            $this->xml_semantic_status
        ));
    }

    public function testItReturnsFalseWhenTheValueIsAnOpenValueInXML(): void
    {
        $this->assertFalse($this->value_checker->isValueAPossibleDoneValueInXMLImport(
            $this->xml_to_do_value,
            $this->xml_semantic_status
        ));

        $this->assertFalse($this->value_checker->isValueAPossibleDoneValueInXMLImport(
            $this->xml_on_going_value,
            $this->xml_semantic_status
        ));
    }

    public function testItReturnsFalseWhenTheValueIsHiddenInXML(): void
    {
        $this->assertFalse($this->value_checker->isValueAPossibleDoneValueInXMLImport(
            $this->xml_hidden_value,
            $this->xml_semantic_status
        ));
    }
}
