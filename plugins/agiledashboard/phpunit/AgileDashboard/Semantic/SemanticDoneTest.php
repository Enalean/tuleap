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

namespace Tuleap\AgileDashboard\Semantic;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker_FormElement_Field_List_Bind_StaticValue;

class SemanticDoneTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SemanticDoneValueChecker
     */
    private $value_checker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Dao\SemanticDoneDao
     */
    private $dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_Semantic_Status
     */
    private $semantic_status;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker
     */
    private $tracker;
    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue
     */
    private $done_value;
    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue
     */
    private $on_going_value;

    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue
     */
    private $to_do_value;

    protected function setUp(): void
    {
        $this->to_do_value    = new Tracker_FormElement_Field_List_Bind_StaticValue(1, 'todo', '', 1, false);
        $this->on_going_value = new Tracker_FormElement_Field_List_Bind_StaticValue(2, 'on-going', '', 2, false);
        $this->done_value     = new Tracker_FormElement_Field_List_Bind_StaticValue(3, 'done', '', 3, false);

        $this->tracker         = \Mockery::mock(\Tracker::class);
        $this->semantic_status = \Mockery::spy(\Tracker_Semantic_Status::class);
        $this->dao             = \Mockery::spy(\Tuleap\AgileDashboard\Semantic\Dao\SemanticDoneDao::class);
        $this->value_checker   = \Mockery::spy(\Tuleap\AgileDashboard\Semantic\SemanticDoneValueChecker::class);
    }

    public function testItExportsTheSemanticInXml(): void
    {
        $this->semantic_status->shouldReceive('getOpenValues')->andReturns(array(
            1,
            2
        ));

        $field = \Mockery::spy(\Tracker_FormElement_Field_List::class)->shouldReceive('getId')->andReturns(101)->getMock();

        $field->shouldReceive('getAllVisibleValues')->andReturns(array(
            1 => $this->to_do_value,
            2 => $this->on_going_value,
            3 => $this->done_value
        ));

        $this->semantic_status->shouldReceive('getField')->andReturns($field);

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

        $this->assertEquals('done', (string) $xml->semantic->shortname);
        $this->assertEquals('Done', (string) $xml->semantic->label);
        $this->assertEquals('F14-V68', (string) $xml->semantic->closed_values->closed_value[0]['REF']);
    }

    public function testItExportsNothingIfNoSemanticStatusDefined(): void
    {
        $this->semantic_status->shouldReceive('getField')->andReturns(null);

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

        $this->assertEquals('', (string) $xml->semantic);
    }
}
