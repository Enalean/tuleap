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

    private $plannings;
    /**
     *
     * @var SimpleXMLElement
     */
    private $xml_tree;

    private $planning1;
    private $planning2;

    public function setUp() {
        parent::setUp();

        $this->planning1 = mock('Planning');
        $this->planning2 = mock('Planning');

        $this->plannings = array(
            $this->planning1,
            $this->planning2,
        );

        stub($this->planning1)->getName()->returns('abcd');
        stub($this->planning2)->getName()->returns('abcd');

        stub($this->planning1)->getPlanTitle()->returns('efgh');
        stub($this->planning2)->getPlanTitle()->returns('efgh');

        stub($this->planning1)->getPlanningTrackerId()->returns('ijklmon');
        stub($this->planning2)->getPlanningTrackerId()->returns('ijklmon');

        stub($this->planning1)->getBacklogTitle()->returns('p q r');
        stub($this->planning2)->getBacklogTitle()->returns('p q r');

        $backlog_tracker1 = mock('Tracker');
        $backlog_tracker2 = mock('Tracker');

        stub($backlog_tracker1)->getId()->returns('stu vw x y   z');
        stub($backlog_tracker2)->getId()->returns('stu vw x y   z');

        stub($this->planning1)->getBacklogTracker()->returns($backlog_tracker1);
        stub($this->planning2)->getBacklogTracker()->returns($backlog_tracker2);

        $data = '<?xml version="1.0" encoding="UTF-8"?>
                 <plannings />';

        $this->xml_tree = new SimpleXMLElement($data);
    }

    public function itUpdatesASimpleXMlElement() {
        $exporter = new AgileDashboard_XMLExporter();

        $xml = $this->xml_tree;
        $exporter->export($this->xml_tree, $this->plannings);

        $this->assertEqual($xml, $this->xml_tree);
    }

    public function itCreatesAnXMLEntryForEachPlanningShortAccess() {
        $exporter = new AgileDashboard_XMLExporter();
        $exporter->export($this->xml_tree, $this->plannings);

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
        $exporter->export($this->xml_tree, $this->plannings);

        $plannings = AgileDashboard_XMLExporter::NODE_PLANNINGS;

        foreach ($this->xml_tree->$plannings->children() as $planning) {
            $attributes = $planning->attributes();

            $this->assertEqual( (string) $attributes[PlanningParameters::NAME], 'abcd');
            $this->assertEqual( (string) $attributes[PlanningParameters::PLANNING_TITLE], 'efgh');
            $this->assertEqual( (string) $attributes[PlanningParameters::BACKLOG_TITLE], 'p q r');
            
            $expected_planning_tracker_id = AgileDashboard_XMLExporter::TRACKER_ID_PREFIX.'ijklmon';
            $expected_backlog_tracker_id  = AgileDashboard_XMLExporter::TRACKER_ID_PREFIX.'stu vw x y   z';
            
            $this->assertEqual( (string) $attributes[PlanningParameters::PLANNING_TRACKER_ID], $expected_planning_tracker_id);
            $this->assertEqual( (string) $attributes[PlanningParameters::BACKLOG_TRACKER_ID], $expected_backlog_tracker_id);
        }
    }

    public function itThrowsAnExceptionIfPlanningNameIsEmpty() {
        $planning = mock('Planning');

        $plannings = array(
            $planning,
        );

        stub($planning)->getName()->returns(null);
        stub($planning)->getPlanTitle()->returns('efgh');
        stub($planning)->getPlanningTrackerId()->returns('ijklmon');
        stub($planning)->getBacklogTitle()->returns('p q r');

        $backlog_tracker = mock('Tracker');
        stub($backlog_tracker)->getId()->returns('stu vw x y   z');
        stub($planning)->getBacklogTracker()->returns($backlog_tracker);

        $this->expectException('AgileDashboard_XMLExporterUnableToGetValueException');

        $exporter = new AgileDashboard_XMLExporter();
        $exporter->export($this->xml_tree, $plannings);
    }

    public function itThrowsAnExceptionIfPlanningTitleIsEmpty() {
        $planning = mock('Planning');

        $plannings = array(
            $planning,
        );

        stub($planning)->getName()->returns('abc d');
        stub($planning)->getPlanTitle()->returns('');
        stub($planning)->getPlanningTrackerId()->returns('ijklmon');
        stub($planning)->getBacklogTitle()->returns('p q r');

        $backlog_tracker = mock('Tracker');
        stub($backlog_tracker)->getId()->returns('stu vw x y   z');
        stub($planning)->getBacklogTracker()->returns($backlog_tracker);

        $this->expectException('AgileDashboard_XMLExporterUnableToGetValueException');

        $exporter = new AgileDashboard_XMLExporter();
        $exporter->export($this->xml_tree, $plannings);
    }

    public function itThrowsAnExceptionIfBacklogTitleIsEmpty() {
        $planning = mock('Planning');

        $plannings = array(
            $planning,
        );

        stub($planning)->getName()->returns('abc d');
        stub($planning)->getPlanTitle()->returns('efgh');
        stub($planning)->getPlanningTrackerId()->returns(45);
        stub($planning)->getBacklogTitle()->returns(null);

        $backlog_tracker = mock('Tracker');
        stub($backlog_tracker)->getId()->returns('stu vw x y   z');
        stub($planning)->getBacklogTracker()->returns($backlog_tracker);

        $this->expectException('AgileDashboard_XMLExporterUnableToGetValueException');

        $exporter = new AgileDashboard_XMLExporter();
        $exporter->export($this->xml_tree, $plannings);
    }

    public function itThrowsAnExceptionIfPlanningTrackerIdIsEmpty() {
        $planning = mock('Planning');

        $plannings = array(
            $planning,
        );

        stub($planning)->getName()->returns('abc d');
        stub($planning)->getPlanTitle()->returns('efgh');
        stub($planning)->getPlanningTrackerId()->returns(null);
        stub($planning)->getBacklogTitle()->returns('p q r');

        $backlog_tracker = mock('Tracker');
        stub($backlog_tracker)->getId()->returns('stu vw x y   z');
        stub($planning)->getBacklogTracker()->returns($backlog_tracker);

        $this->expectException('AgileDashboard_XMLExporterUnableToGetValueException');

        $exporter = new AgileDashboard_XMLExporter();
        $exporter->export($this->xml_tree, $plannings);
    }

    public function itThrowsAnExceptionIfBacklogTrackerIdIsEmpty() {
        $planning = mock('Planning');

        $plannings = array(
            $planning,
        );

        stub($planning)->getName()->returns('abc d');
        stub($planning)->getPlanTitle()->returns('efgh');
        stub($planning)->getPlanningTrackerId()->returns(78);
        stub($planning)->getBacklogTitle()->returns('p q r');

        $backlog_tracker = mock('Tracker');
        stub($backlog_tracker)->getId()->returns(null);
        stub($planning)->getBacklogTracker()->returns($backlog_tracker);

        $this->expectException('AgileDashboard_XMLExporterUnableToGetValueException');

        $exporter = new AgileDashboard_XMLExporter();
        $exporter->export($this->xml_tree, $plannings);
    }
}
?>
