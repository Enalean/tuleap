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

use SimpleXMLElement;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use TuleapTestCase;

require_once dirname(__FILE__) . '/../../../bootstrap.php';

class SemanticDoneValueCheckerTest extends TuleapTestCase
{
    /**
     * @var SemanticDoneValueChecker
     */
    private $value_checker;

    public function setUp()
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

        $this->semantic_status = stub('Tracker_Semantic_Status')->getOpenValues()->returns(array(
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

    public function itReturnsTrueWhenTheValueCouldBeAddedAsADoneValue()
    {
        $this->assertTrue($this->value_checker->isValueADoneValue($this->done_value, $this->semantic_status));
    }

    public function itReturnsFalseWhenTheValueIsAnOpenValue()
    {
        $this->assertFalse($this->value_checker->isValueADoneValue($this->to_do_value, $this->semantic_status));
        $this->assertFalse($this->value_checker->isValueADoneValue($this->on_going_value, $this->semantic_status));
    }

    public function itReturnsFalseWhenTheValueIsHidden()
    {
        $this->assertFalse($this->value_checker->isValueADoneValue($this->hidden_value, $this->semantic_status));
    }

    public function itReturnsTrueWhenTheValueCouldBeAddedAsADoneValueInXML()
    {
        $this->assertTrue($this->value_checker->isValueADoneValueInXMLImport(
            $this->xml_done_value,
            $this->xml_semantic_status
        ));
    }

    public function itReturnsFalseWhenTheValueIsAnOpenValueInXML()
    {
        $this->assertFalse($this->value_checker->isValueADoneValueInXMLImport(
            $this->xml_to_do_value,
            $this->xml_semantic_status
        ));

        $this->assertFalse($this->value_checker->isValueADoneValueInXMLImport(
            $this->xml_on_going_value,
            $this->xml_semantic_status
        ));
    }

    public function itReturnsFalseWhenTheValueIsHiddenInXML()
    {
        $this->assertFalse($this->value_checker->isValueADoneValueInXMLImport(
            $this->xml_hidden_value,
            $this->xml_semantic_status
        ));
    }
}
