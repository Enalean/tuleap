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
require_once('bootstrap.php');
class Tracker_SemanticManagerTest extends TuleapTestCase {
    private $user;
    private $semantic_title;
    private $unreadable_semantic_title;
    private $semantic_contributor;
    private $unreadable_semantic_contributor;
    private $semantic_status;
    private $unreadable_semantic_status;
    private $semantic_tooltip;
    private $not_defined_semantic_title;
    private $not_defined_semantic_status;
    private $not_defined_semantic_contributor;
    private $open_values = array(803,2354,2943);

    public function setUp() {
        $this->user        = aUser()->build();
        $this->tracker     = mock('Tracker');
        $summary_field     = mock('Tracker_FormElement_Field_Text');
        stub($summary_field)->getName()->returns('summary');
        stub($summary_field)->userCanRead()->returns(true);
        $assigned_to_field = mock('Tracker_FormElement_Field_List');
        stub($assigned_to_field)->getName()->returns('assigned_to');
        stub($assigned_to_field)->userCanRead()->returns(true);
        $values_field      = mock('Tracker_FormElement_Field_List');
        stub($values_field)->getName()->returns('status');
        stub($values_field)->userCanRead()->returns(true);

        $unreable_field      = mock('Tracker_FormElement_Field_Text');
        stub($unreable_field)->getName()->returns('whatever');
        stub($unreable_field)->userCanRead()->returns(false);
        $unreable_field_list = mock('Tracker_FormElement_Field_List');
        stub($unreable_field_list)->getName()->returns('whatever');
        stub($unreable_field_list)->userCanRead()->returns(false);

        $this->semantic_title                   = new Tracker_Semantic_Title($this->tracker, $summary_field);
        $this->unreadable_semantic_title        = new Tracker_Semantic_Title($this->tracker, $unreable_field);
        $this->semantic_contributor             = new Tracker_Semantic_Contributor($this->tracker, $assigned_to_field);
        $this->unreadable_semantic_contributor  = new Tracker_Semantic_Contributor($this->tracker, $unreable_field_list);
        $this->semantic_status                  = new Tracker_Semantic_Status($this->tracker, $values_field, $this->open_values);
        $this->unreadable_semantic_status       = new Tracker_Semantic_Status($this->tracker, $unreable_field_list, $this->open_values);
        $this->semantic_tooltip                 = new Tracker_Tooltip($this->tracker);
        $this->not_defined_semantic_title       = new Tracker_Semantic_Title($this->tracker);
        $this->not_defined_semantic_status      = new Tracker_Semantic_Status($this->tracker);
        $this->not_defined_semantic_contributor = new Tracker_Semantic_Contributor($this->tracker);

        $this->semantic_manager = partial_mock('Tracker_SemanticManager', array('getSemantics', 'getSemanticOrder'), array($this->tracker));
        stub($this->semantic_manager)->getSemanticOrder()->returns(
            array('title', 'status', 'contributor')
        );
    }

    public function itReturnsEmptyIfUserCannoAccessTitleSemanticField() {
        stub($this->semantic_manager)->getSemantics()->returns(array(
            'title' => $this->unreadable_semantic_title
        ));
        $result = $this->semantic_manager->exportToSOAP($this->user);
        $this->assertEqual($result['title']['field_name'], '');
    }

    public function itReturnsTheFieldNameOfTheTitleSemantic() {
        stub($this->semantic_manager)->getSemantics()->returns(array(
            'title' => $this->semantic_title
        ));
        $result = $this->semantic_manager->exportToSOAP($this->user);
        $this->assertEqual($result['title']['field_name'], 'summary');
    }

    public function itReturnsEmptyIfNoTitleSemantic() {
        stub($this->semantic_manager)->getSemantics()->returns(array(
            'title' => $this->not_defined_semantic_title
        ));
        $result = $this->semantic_manager->exportToSOAP($this->user);
        $this->assertEqual($result['title']['field_name'], '');
    }

    public function itReturnsTheFieldNameOfTheContributorSemantic() {
        stub($this->semantic_manager)->getSemantics()->returns(array(
            'contributor' => $this->semantic_contributor
        ));
        $result = $this->semantic_manager->exportToSOAP($this->user);
        $this->assertEqual($result['contributor']['field_name'], 'assigned_to');
    }

    public function itReturnsEmptyIfUserCannoAccessContributorSemantic() {
        stub($this->semantic_manager)->getSemantics()->returns(array(
            'contributor' => $this->unreadable_semantic_contributor
        ));
        $result = $this->semantic_manager->exportToSOAP($this->user);
        $this->assertEqual($result['contributor']['field_name'], '');
    }

    public function itReturnsEmptyIfNoContributorSemantic() {
        stub($this->semantic_manager)->getSemantics()->returns(array(
            'contributor' => $this->not_defined_semantic_contributor
        ));
        $result = $this->semantic_manager->exportToSOAP($this->user);
        $this->assertEqual($result['contributor']['field_name'], '');
    }

    public function itReturnsEmptyIfUserCannoAccessStatusSemantic() {
        stub($this->semantic_manager)->getSemantics()->returns(array(
            'status' => $this->unreadable_semantic_status
        ));
        $result = $this->semantic_manager->exportToSOAP($this->user);
        $this->assertEqual($result['status']['field_name'], '');
        $this->assertEqual($result['status']['values'], array());
    }

    public function itReturnsTheFieldNameAndValuesOfTheStatusSemantic() {
        stub($this->semantic_manager)->getSemantics()->returns(array(
            'status' => $this->semantic_status
        ));
        $result = $this->semantic_manager->exportToSOAP($this->user);
        $this->assertEqual($result['status']['field_name'], 'status');
        $this->assertEqual($result['status']['values'], $this->open_values);
    }

    public function itReturnsEmptyIfNoStatusSemantic() {
        stub($this->semantic_manager)->getSemantics()->returns(array(
            'status' => $this->not_defined_semantic_status
        ));
        $result = $this->semantic_manager->exportToSOAP($this->user);
        $this->assertEqual($result['status']['field_name'], '');
        $this->assertEqual($result['status']['values'], array());
    }

    public function itReturnsSemanticInTheRightOrder() {
        $semantic_manager = partial_mock('Tracker_SemanticManager', array('getSemantics', 'getSemanticOrder'), array($this->tracker));

        stub($semantic_manager)->getSemantics()->returns(array(
            'title'          => $this->semantic_title,
            'contributor'    => $this->semantic_contributor,
            'status'         => $this->semantic_status,
            'random_from_ad' => stub('AgileDashBoard_Semantic_InitialEffort')->exportToSOAP()->returns('bla')
        ));

        stub($semantic_manager)->getSemanticOrder()->returns(
            array('title', 'status', 'contributor', 'random_from_ad')
        );

        $result = $semantic_manager->exportToSOAP($this->user);
        $result_keys = array_keys($result);
        $this->assertEqual($result_keys, array('title', 'status', 'contributor', 'random_from_ad'));
    }

    public function itDoesNotExportTooltipSemantic() {
        stub($this->semantic_manager)->getSemantics()->returns(array(
            'tooltip' => $this->semantic_tooltip
        ));

        $result = $this->semantic_manager->exportToSOAP($this->user);
        $this->assertFalse(isset($result['tooltip']));
    }
}

?>