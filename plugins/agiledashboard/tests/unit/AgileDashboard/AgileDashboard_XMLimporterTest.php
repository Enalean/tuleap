<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\Planning\XML\XMLExporter;

final class AgileDashboard_XMLimporterTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * @var SimpleXMLElement
     */
    private $xml_object;

    /**
     *
     * @var AgileDashboard_XMLImporter
     */
    private $importer;

    private $tracker_mappings;

    protected function setUp(): void
    {
        $this->importer = new AgileDashboard_XMLImporter();

        $name                = PlanningParameters::NAME;
        $backlog_title       = PlanningParameters::BACKLOG_TITLE;
        $plan_title          = PlanningParameters::PLANNING_TITLE;
        $planning_tracker_id = PlanningParameters::PLANNING_TRACKER_ID;

        $default_xml      = '<?xml version="1.0" encoding="UTF-8"?>
                 <agiledashboard>
                    <plannings>
                        <planning ' .
                            $name . '="Sprint Planning" ' .
                            $plan_title . '="Sprint Plan" ' .
                            $planning_tracker_id . '="T11" ' .
                            $backlog_title . '="Release Backlog">
                            <backlogs>
                                <backlog>T14</backlog>
                            </backlogs>
                        </planning>
                        <planning ' .
                            $name . '="Sprint Planning" ' .
                            $plan_title . '="Sprint Plan" ' .
                            $planning_tracker_id . '="T11" ' .
                            $backlog_title . '="Release Backlog">
                            <backlogs>
                                <backlog>T14</backlog>
                                <backlog>T15</backlog>
                            </backlogs>
                        </planning>
                    </plannings>
                 </agiledashboard>';
        $this->xml_object = new SimpleXMLElement($default_xml);

        $this->tracker_mappings = [
            'T11' => 154,
            'T14' => 8,
            'T15' => 96,
        ];
    }

    public function testItReturnsAnEmptyArrayIfNoPlanningsExist(): void
    {
        $xml        = '<?xml version="1.0" encoding="UTF-8"?>
                 <agiledashboard />';
        $xml_object = new SimpleXMLElement($xml);

        $data = $this->importer->toArray($xml_object, $this->tracker_mappings);

        $this->assertIsArray($data);
    }

    public function testItReturnsAnArrayForEachPlanning(): void
    {
        $data = $this->importer->toArray($this->xml_object, $this->tracker_mappings);

        $this->assertIsArray($data);
        $this->assertIsArray($data[XMLExporter::NODE_PLANNINGS]);

        $this->assertCount(2, $data[XMLExporter::NODE_PLANNINGS]);
    }

    public function testItReturnsAnArrayOfPlanningParameterValuesForAPlanning(): void
    {
        $data      = $this->importer->toArray($this->xml_object, $this->tracker_mappings);
        $plannings = $data[XMLExporter::NODE_PLANNINGS];

        $a_planning = $plannings[0];

        $this->assertTrue(array_key_exists(PlanningParameters::NAME, $a_planning));
        $this->assertTrue(array_key_exists(PlanningParameters::BACKLOG_TITLE, $a_planning));
        $this->assertTrue(array_key_exists(PlanningParameters::PLANNING_TITLE, $a_planning));
        $this->assertTrue(array_key_exists(PlanningParameters::PLANNING_TRACKER_ID, $a_planning));
        $this->assertTrue(array_key_exists(PlanningParameters::BACKLOG_TRACKER_IDS, $a_planning));
    }

    public function testItReturnsCorrectTrackerIdsForAPlanning(): void
    {
        $data       = $this->importer->toArray($this->xml_object, $this->tracker_mappings);
        $plannings  = $data[XMLExporter::NODE_PLANNINGS];
        $a_planning = $plannings[0];

        $this->assertTrue(array_key_exists(PlanningParameters::BACKLOG_TRACKER_IDS, $a_planning));
        $this->assertTrue(array_key_exists(PlanningParameters::PLANNING_TRACKER_ID, $a_planning));

        $this->assertEquals([8], $a_planning[PlanningParameters::BACKLOG_TRACKER_IDS]);
        $this->assertEquals(154, $a_planning[PlanningParameters::PLANNING_TRACKER_ID]);
    }

    public function testItReturnsSeveralBacklogTrackers(): void
    {
        $data       = $this->importer->toArray($this->xml_object, $this->tracker_mappings);
        $plannings  = $data[XMLExporter::NODE_PLANNINGS];
        $a_planning = $plannings[1];

        $this->assertEquals([8, 96], $a_planning[PlanningParameters::BACKLOG_TRACKER_IDS]);
    }

    public function testItThrowsAnExceptionIfTrackerMappingsAreInvalid(): void
    {
        $tracker_mappings = [];

        $this->expectException(\AgileDashboard_XMLImporterInvalidTrackerMappingsException::class);

        $this->importer->toArray($this->xml_object, $tracker_mappings);
    }
}
