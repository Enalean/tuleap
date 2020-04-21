<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
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

namespace Tuleap\AgileDashboard\Semantic;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_Semantic_Status;

require_once dirname(__FILE__) . '/../../bootstrap.php';

class SemanticDoneValueCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var SemanticDoneValueChecker
     */
    private $value_checker;

    public function setUp(): void
    {
        parent::setUp();

        $this->to_do_value    = new Tracker_FormElement_Field_List_Bind_StaticValue(1, 'todo', '', 1, false);
        $this->on_going_value = new Tracker_FormElement_Field_List_Bind_StaticValue(2, 'on-going', '', 2, false);
        $this->done_value     = new Tracker_FormElement_Field_List_Bind_StaticValue(3, 'done', '', 3, false);
        $this->hidden_value   = new Tracker_FormElement_Field_List_Bind_StaticValue(4, 'hidden', '', 4, true);

        $this->xml_to_do_value    = new Tracker_FormElement_Field_List_Bind_StaticValue("F1", 'todo', '', 1, false);
        $this->xml_on_going_value = new Tracker_FormElement_Field_List_Bind_StaticValue("F2", 'on-going', '', 2, false);
        $this->xml_done_value     = new Tracker_FormElement_Field_List_Bind_StaticValue("F3", 'done', '', 3, false);
        $this->xml_hidden_value   = new Tracker_FormElement_Field_List_Bind_StaticValue("F4", 'hidden', '', 4, true);

        $this->semantic_status = Mockery::spy(Tracker_Semantic_Status::class);
        $this->semantic_status->shouldReceive('getOpenValues')->andReturn(array(
            1,
            2
        ));

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

    public function testItReturnsTrueWhenTheValueCouldBeAddedAsADoneValue()
    {
        $this->assertTrue($this->value_checker->isValueAPossibleDoneValue($this->done_value, $this->semantic_status));
    }

    public function testItReturnsFalseWhenTheValueIsAnOpenValue()
    {
        $this->assertFalse($this->value_checker->isValueAPossibleDoneValue($this->to_do_value, $this->semantic_status));
        $this->assertFalse($this->value_checker->isValueAPossibleDoneValue($this->on_going_value, $this->semantic_status));
    }

    public function testItReturnsFalseWhenTheValueIsHidden()
    {
        $this->assertFalse($this->value_checker->isValueAPossibleDoneValue($this->hidden_value, $this->semantic_status));
    }

    public function testItReturnsTrueWhenTheValueCouldBeAddedAsADoneValueInXML()
    {
        $this->assertTrue($this->value_checker->isValueAPossibleDoneValueInXMLImport(
            $this->xml_done_value,
            $this->xml_semantic_status
        ));
    }

    public function testItReturnsFalseWhenTheValueIsAnOpenValueInXML()
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

    public function testItReturnsFalseWhenTheValueIsHiddenInXML()
    {
        $this->assertFalse($this->value_checker->isValueAPossibleDoneValueInXMLImport(
            $this->xml_hidden_value,
            $this->xml_semantic_status
        ));
    }
}
