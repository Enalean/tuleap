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

class AgileDashboard_XMLimporterTest extends TuleapTestCase {

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

    public function setUp() {
        parent::setUp();

        $this->importer = new AgileDashboard_XMLImporter();

        $name                 = AgileDashboard_XMLExporter::ATTRIBUTE_PLANNING_NAME;
        $plan_title           = AgileDashboard_XMLExporter::ATTRIBUTE_PLANNING_TITLE;
        $backlog_title        = AgileDashboard_XMLExporter::ATTRIBUTE_PLANNING_BACKLOG_TITLE;
        $item_tracker_id      = AgileDashboard_XMLExporter::ATTRIBUTE_PLANNING_ITEM_TRACKER_ID;
        $milestone_tracker_id = AgileDashboard_XMLExporter::ATTRIBUTE_PLANNING_MILESTONE_TRACKER_ID;

        $default_xml = '<?xml version="1.0" encoding="UTF-8"?>
                 <agiledashboard>
                    <plannings>
                        <planning '.
                            $name.'="Sprint Planning" '.
                            $plan_title.'="Sprint Plan" '.
                            $item_tracker_id.'="T11" '.
                            $backlog_title.'="Release Backlog" '.
                            $milestone_tracker_id.'="T11"/>
                        <planning '.
                            $name.'="Sprint Planning" '.
                            $plan_title.'="Sprint Plan" '.
                            $item_tracker_id.'="T11" '.
                            $backlog_title.'="Release Backlog" '.
                            $milestone_tracker_id.'="T11"/>
                    </plannings>
                 </agiledashboard>';
        $this->xml_object = new SimpleXMLElement($default_xml);
    }

    public function itReturnsAnEmptyArrayIfNoPlanningsExist() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
                 <agiledashboard />';
        $xml_object = new SimpleXMLElement($xml);

        $data = $this->importer->toArray($xml_object);

        $this->assertTrue(is_array($data));
    }

    public function itReturnsAnArrayForEachPlanning() {
        $data = $this->importer->toArray($this->xml_object);

        $this->assertTrue(is_array($data));
        $this->assertCount($data, 2);
    }

    public function itReturnsAnArrayOfPlanningParameterValuesForAPlanning() {
        $data = $this->importer->toArray($this->xml_object);

        $a_planning = $data[0];

        $this->assertTrue(array_key_exists(PlanningParameters::NAME, $a_planning));
        $this->assertTrue(array_key_exists(PlanningParameters::BACKLOG_TITLE, $a_planning));
        $this->assertTrue(array_key_exists(PlanningParameters::PLANNING_TITLE, $a_planning));
        $this->assertTrue(array_key_exists(PlanningParameters::BACKLOG_TRACKER_ID, $a_planning));
        $this->assertTrue(array_key_exists(PlanningParameters::PLANNING_TRACKER_ID, $a_planning));
    }
}
?>
