<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
require_once dirname(__FILE__).'/../../bootstrap.php';

class AgileDashboard_XMLimporterTest extends TuleapTestCase
{

    /**
     *
     * @var SimpleXMLElement
     */
    private $xml_object;

    /**
     *
     * @var AgileDashboard_XMLImporter
     */
    private $importer;

    private $tracker_mappings;

    public function setUp()
    {
        parent::setUp();

        $this->importer = new AgileDashboard_XMLImporter();

        $name                 = PlanningParameters::NAME;
        $backlog_title        = PlanningParameters::BACKLOG_TITLE;
        $plan_title           = PlanningParameters::PLANNING_TITLE;
        $planning_tracker_id  = PlanningParameters::PLANNING_TRACKER_ID;

        $default_xml = '<?xml version="1.0" encoding="UTF-8"?>
                 <agiledashboard>
                    <plannings>
                        <planning '.
                            $name.'="Sprint Planning" '.
                            $plan_title.'="Sprint Plan" '.
                            $planning_tracker_id.'="T11" '.
                            $backlog_title.'="Release Backlog">
                            <backlogs>
                                <backlog>T14</backlog>
                            </backlogs>
                        </planning>
                        <planning '.
                            $name.'="Sprint Planning" '.
                            $plan_title.'="Sprint Plan" '.
                            $planning_tracker_id.'="T11" '.
                            $backlog_title.'="Release Backlog">
                            <backlogs>
                                <backlog>T14</backlog>
                                <backlog>T15</backlog>
                            </backlogs>
                        </planning>
                    </plannings>
                 </agiledashboard>';
        $this->xml_object = new SimpleXMLElement($default_xml);

        $this->tracker_mappings = array(
            'T11' => 154,
            'T14' => 8,
            'T15' => 96,
        );
    }

    public function itReturnsAnEmptyArrayIfNoPlanningsExist()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
                 <agiledashboard />';
        $xml_object = new SimpleXMLElement($xml);

        $data = $this->importer->toArray($xml_object, $this->tracker_mappings);

        $this->assertTrue(is_array($data));
    }

    public function itReturnsAnArrayForEachPlanning()
    {
        $data = $this->importer->toArray($this->xml_object, $this->tracker_mappings);

        $this->assertTrue(is_array($data));
        $this->assertTrue(is_array($data[AgileDashboard_XMLExporter::NODE_PLANNINGS]));

        $this->assertCount($data[AgileDashboard_XMLExporter::NODE_PLANNINGS], 2);
    }

    public function itReturnsAnArrayOfPlanningParameterValuesForAPlanning()
    {
        $data = $this->importer->toArray($this->xml_object, $this->tracker_mappings);
        $plannings = $data[AgileDashboard_XMLExporter::NODE_PLANNINGS];

        $a_planning = $plannings[0];

        $this->assertTrue(array_key_exists(PlanningParameters::NAME, $a_planning));
        $this->assertTrue(array_key_exists(PlanningParameters::BACKLOG_TITLE, $a_planning));
        $this->assertTrue(array_key_exists(PlanningParameters::PLANNING_TITLE, $a_planning));
        $this->assertTrue(array_key_exists(PlanningParameters::PLANNING_TRACKER_ID, $a_planning));
        $this->assertTrue(array_key_exists(PlanningParameters::BACKLOG_TRACKER_IDS, $a_planning));
    }

    public function itReturnsCorrectTrackerIdsForAPlanning()
    {
        $data = $this->importer->toArray($this->xml_object, $this->tracker_mappings);
        $plannings = $data[AgileDashboard_XMLExporter::NODE_PLANNINGS];
        $a_planning = $plannings[0];

        $this->assertTrue(array_key_exists(PlanningParameters::BACKLOG_TRACKER_IDS, $a_planning));
        $this->assertTrue(array_key_exists(PlanningParameters::PLANNING_TRACKER_ID, $a_planning));

        $this->assertEqual($a_planning[PlanningParameters::BACKLOG_TRACKER_IDS], array(8));
        $this->assertEqual($a_planning[PlanningParameters::PLANNING_TRACKER_ID], 154);
    }

    public function itReturnsSeveralBacklogTrackers()
    {
        $data = $this->importer->toArray($this->xml_object, $this->tracker_mappings);
        $plannings = $data[AgileDashboard_XMLExporter::NODE_PLANNINGS];
        $a_planning = $plannings[1];

        $this->assertEqual($a_planning[PlanningParameters::BACKLOG_TRACKER_IDS], array(8, 96));
    }

    public function itThrowsAnExceptionIfTrackerMappingsAreInvalid()
    {
        $tracker_mappings = array();

        $this->expectException('AgileDashboard_XMLImporterInvalidTrackerMappingsException');

        $this->importer->toArray($this->xml_object, $tracker_mappings);
    }
}
