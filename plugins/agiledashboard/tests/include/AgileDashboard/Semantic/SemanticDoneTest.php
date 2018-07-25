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

class SemanticDoneTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->to_do_value    = new Tracker_FormElement_Field_List_Bind_StaticValue(1, 'todo', '', 1, false);
        $this->on_going_value = new Tracker_FormElement_Field_List_Bind_StaticValue(2, 'on-going', '', 2, false);
        $this->done_value     = new Tracker_FormElement_Field_List_Bind_StaticValue(3, 'done', '', 3, false);

        $this->tracker         = aMockTracker()->build();
        $this->semantic_status = mock('Tracker_Semantic_Status');
        $this->dao             = mock('Tuleap\AgileDashboard\Semantic\Dao\SemanticDoneDao');
        $this->value_checker   = mock('Tuleap\AgileDashboard\Semantic\SemanticDoneValueChecker');
    }

    public function itExportsTheSemanticInXml()
    {
        stub($this->semantic_status)->getOpenValues()->returns(array(
            1,
            2
        ));

        $field = stub('Tracker_FormElement_Field_List')->getId()->returns(101);

        stub($field)->getAllVisibleValues()->returns(array(
            1 => $this->to_do_value,
            2 => $this->on_going_value,
            3 => $this->done_value
        ));

        stub($this->semantic_status)->getField()->returns($field);

        $xml               = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_xml_mapping = array(
            'F14' => 101,
            'values' => array(
                'F14-V66' => 1,
                'F14-V67' => 2,
                'F14-V68' => 3,
            )
        );

        $semantic_done = new SemanticDone(
            $this->tracker,
            $this->semantic_status,
            $this->dao,
            $this->value_checker,
            array(3 => $this->done_value)
        );

        $semantic_done->exportToXML($xml, $array_xml_mapping);

        $this->assertEqual((string)$xml->semantic->shortname, 'done');
        $this->assertEqual((string)$xml->semantic->label, 'Done');
        $this->assertEqual((string)$xml->semantic->closed_values->closed_value[0]['REF'], 'F14-V68');
    }

    public function itExportsNothingIfNoSemanticStatusDefined()
    {
        stub($this->semantic_status)->getField()->returns(null);

        $xml               = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_xml_mapping = array(
            'F14' => 101,
            'values' => array(
                'F14-V66' => 1,
                'F14-V67' => 2,
                'F14-V68' => 3,
            )
        );

        $semantic_done = new SemanticDone(
            $this->tracker,
            $this->semantic_status,
            $this->dao,
            $this->value_checker,
            array()
        );

        $semantic_done->exportToXML($xml, $array_xml_mapping);

        $this->assertEqual((string) $xml->semantic, '');
    }
}
