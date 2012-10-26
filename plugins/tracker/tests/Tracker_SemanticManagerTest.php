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
require_once(dirname(__FILE__) . '/builders/all.php');
require_once(dirname(__FILE__) . '/../include/Tracker/Semantic/Tracker_SemanticManager.class.php');
require_once(dirname(__FILE__) . '/../include/Tracker/Semantic/Tracker_Semantic_Title.class.php');
require_once(dirname(__FILE__) . '/../include/Tracker/Semantic/Tracker_Semantic_Status.class.php');
require_once(dirname(__FILE__) . '/../include/Tracker/Semantic/Tracker_Semantic_Contributor.class.php');
require_once(dirname(__FILE__) . '/../include/Tracker/Tooltip/Tracker_Tooltip.class.php');

class Tracker_SemanticManagerTest extends TuleapTestCase {

    private $semantic_title;
    private $semantic_contributor;
    private $semantic_status;
    private $semantic_tooltip;
    private $not_defined_semantic_title;
    private $not_defined_semantic_status;
    private $not_defined_semantic_contributor;
    private $open_values = array(803,2354,2943);

    public function setUp() {
        $tracker           = mock('Tracker');
        $summary_field     = aTextField()->withName('summary')->build();
        $assigned_to_field = aSelectBoxField()->withName('assigned_to')->build();
        $values_field      = aSelectBoxField()->withName('status')->build();

        $this->semantic_title                   = new Tracker_Semantic_Title($tracker, $summary_field);
        $this->semantic_contributor             = new Tracker_Semantic_Contributor($tracker, $assigned_to_field);
        $this->semantic_status                  = new Tracker_Semantic_Status($tracker, $values_field, $this->open_values);
        $this->semantic_tooltip                 = new Tracker_Tooltip($tracker);
        $this->not_defined_semantic_title       = new Tracker_Semantic_Title($tracker);
        $this->not_defined_semantic_status      = new Tracker_Semantic_Status($tracker);
        $this->not_defined_semantic_contributor = new Tracker_Semantic_Contributor($tracker);

        $this->semantic_manager = partial_mock('Tracker_SemanticManager', array('getSemantics'), array($tracker));

    }

    public function itReturnsTheFieldNameOfTheTitleSemantic() {
        stub($this->semantic_manager)->getSemantics()->returns(array(
            'title' => $this->semantic_title
        ));
        $result = $this->semantic_manager->exportToSOAP();
        $this->assertEqual($result['title']['field_name'], 'summary');
    }

    public function itReturnsEmptyIfNoTitleSemantic() {
        stub($this->semantic_manager)->getSemantics()->returns(array(
            'title' => $this->not_defined_semantic_title
        ));
        $result = $this->semantic_manager->exportToSOAP();
        $this->assertEqual($result['title']['field_name'], '');
    }

    public function itReturnsTheFieldNameOfTheContributorSemantic() {
        stub($this->semantic_manager)->getSemantics()->returns(array(
            'contributor' => $this->semantic_contributor
        ));
        $result = $this->semantic_manager->exportToSOAP();
        $this->assertEqual($result['contributor']['field_name'], 'assigned_to');
    }

    public function itReturnsEmptyIfNoContributorSemantic() {
        stub($this->semantic_manager)->getSemantics()->returns(array(
            'contributor' => $this->not_defined_semantic_contributor
        ));
        $result = $this->semantic_manager->exportToSOAP();
        $this->assertEqual($result['contributor']['field_name'], '');
    }
    public function itReturnsTheFieldNameAndValuesOfTheStatusSemantic() {
        stub($this->semantic_manager)->getSemantics()->returns(array(
            'status' => $this->semantic_status
        ));
        $result = $this->semantic_manager->exportToSOAP();
        $this->assertEqual($result['status']['field_name'], 'status');
        $this->assertEqual($result['status']['values'], $this->open_values);
    }

    public function itReturnsEmptyIfNoStatusSemantic() {
        stub($this->semantic_manager)->getSemantics()->returns(array(
            'status' => $this->not_defined_semantic_status
        ));
        $result = $this->semantic_manager->exportToSOAP();
        $this->assertEqual($result['status']['field_name'], '');
        $this->assertEqual($result['status']['values'], array());
    }

    public function itReturnsSemanticInTheRightOrder() {
        stub($this->semantic_manager)->getSemantics()->returns(array(
            'title'       => $this->semantic_title,
            'contributor' => $this->semantic_contributor,
            'status'      => $this->semantic_status
        ));

        $result = $this->semantic_manager->exportToSOAP();
        $result_keys = array_keys($result);
        $this->assertEqual($result_keys, array('title', 'status', 'contributor'));
    }

    public function itDoesNotExportTooltipSemantic() {
        stub($this->semantic_manager)->getSemantics()->returns(array(
            'tooltip' => $this->semantic_tooltip
        ));

        $result = $this->semantic_manager->exportToSOAP();
        $this->assertFalse(isset($result['tooltip']));
    }
}

?>