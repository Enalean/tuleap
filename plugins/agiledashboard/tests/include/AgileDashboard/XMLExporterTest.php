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

class AgileDashboard_XMLExporterTest extends TuleapTestCase {

    private $planning_short_access_set;
    /**
     *
     * @var SimpleXMLElement
     */
    private $xml_tree;

    private $planning1;
    private $planning2;

    private $planning_short_access1;
    private $planning_short_access2;

    private $planning_milestone1;
    private $planning_milestone2;



    public function setUp() {
        parent::setUp();

        $this->planning_short_access1 = mock('Planning_ShortAccess');
        $this->planning_short_access2 = mock('Planning_ShortAccess');

        $this->planning_short_access_set = array(
            $this->planning_short_access1,
            $this->planning_short_access2,
        );

        $this->planning1 = mock('Planning');
        $this->planning2 = mock('Planning');

        $this->planning_milestone1 = mock('Planning_Milestone');
        $this->planning_milestone2 = mock('Planning_Milestone');

        stub($this->planning_short_access1)->getPlanning()->returns($this->planning1);
        stub($this->planning_short_access2)->getPlanning()->returns($this->planning2);

        stub($this->planning_short_access1)->getCurrentMilestone()->returns($this->planning_milestone1);
        stub($this->planning_short_access2)->getCurrentMilestone()->returns($this->planning_milestone2);

        stub($this->planning1)->getName()->returns('abcd');
        stub($this->planning2)->getName()->returns('abcd');

        stub($this->planning1)->getPlanTitle()->returns('efgh');
        stub($this->planning2)->getPlanTitle()->returns('efgh');

        stub($this->planning1)->getPlanningTrackerId()->returns('ijklmon');
        stub($this->planning2)->getPlanningTrackerId()->returns('ijklmon');

        stub($this->planning1)->getBacklogTitle()->returns('p q r');
        stub($this->planning2)->getBacklogTitle()->returns('p q r');

        stub($this->planning_milestone1)->getTrackerId()->returns('stu vw x y   z');
        stub($this->planning_milestone2)->getTrackerId()->returns('stu vw x y   z');

        $data = '<?xml version="1.0" encoding="UTF-8"?>
                 <plannings />';

        $this->xml_tree = new SimpleXMLElement($data);
    }

    public function itUpdatesASimpleXMlElement() {
        $exporter = new AgileDashboard_XMLExporter();

        $xml = $this->xml_tree;
        $exporter->export($this->xml_tree, $this->planning_short_access_set);

        $this->assertEqual($xml, $this->xml_tree);
    }

    public function itCreatesAnXMLEntryForEachPlanningShortAccess() {
        $exporter = new AgileDashboard_XMLExporter();
        $exporter->export($this->xml_tree, $this->planning_short_access_set);

        $this->assertEqual(1, count($this->xml_tree->children()));

        $plannings = AgileDashboard_XMLExporter::NODE_PLANNINGS;

        foreach ($this->xml_tree->children() as $plannings_node) {
            $this->assertEqual(2, count($plannings_node->children()));
            $this->assertEqual($plannings_node->getName(), $plannings);
        }

        foreach ($this->xml_tree->$plannings->children() as $planning) {
            $this->assertEqual($planning->getName(), AgileDashboard_XMLExporter::NODE_PLANNING);
            $this->assertEqual(0, count($planning->children()));
        }
    }

    public function itAddsAttributesForEachPlanningShortAccess() {
        $exporter = new AgileDashboard_XMLExporter();
        $exporter->export($this->xml_tree, $this->planning_short_access_set);

        $plannings = AgileDashboard_XMLExporter::NODE_PLANNINGS;

        foreach ($this->xml_tree->$plannings->children() as $planning) {
            $attributes = $planning->attributes();

            $this->assertEqual( (string) $attributes[AgileDashboard_XMLExporter::ATTRIBUTE_PLANNING_NAME], 'abcd');
            $this->assertEqual( (string) $attributes[AgileDashboard_XMLExporter::ATTRIBUTE_PLANNING_TITLE], 'efgh');
            $this->assertEqual( (string) $attributes[AgileDashboard_XMLExporter::ATTRIBUTE_PLANNING_BACKLOG_TITLE], 'p q r');
            $this->assertEqual( (string) $attributes[AgileDashboard_XMLExporter::ATTRIBUTE_PLANNING_ITEM_TRACKER_ID], 'ijklmon');
            $this->assertEqual( (string) $attributes[AgileDashboard_XMLExporter::ATTRIBUTE_PLANNING_MILESTONE_TRACKER_ID], 'stu vw x y   z');
        }
    }
}
?>
