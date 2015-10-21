<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

require_once dirname(__FILE__).'/../common.php';

class AgileDashboard_KanbanAddInPlaceCheckerTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $this->field_string = stub('Tracker_FormElement_Field_String')->getId()->returns(201);
        $this->field_text   = stub('Tracker_FormElement_Field_Text')->getId()->returns(20);
        $this->field_int    = stub('Tracker_FormElement_Field_Integer')->getId()->returns(30);
        $this->field_list   = stub('Tracker_FormElement_Field_List')->getId()->returns(40);

        $this->used_fields = array(
            $this->field_string,
            $this->field_text,
            $this->field_int,
            $this->field_list,
        );

        $this->user           = mock('PFUser');
        $this->tracker        = mock('Tracker');
        $this->semantic_title = mock('Tracker_Semantic_Title');

        Tracker_Semantic_Title::setInstance($this->semantic_title, $this->tracker);
    }

    public function tearDown() {
        parent::tearDown();

        Tracker_Semantic_Title::clearInstances();
    }

    public function itReturnsTrueIfUserCanAddInPlace() {
        stub($this->field_string)->isRequired()->returns(true);
        stub($this->field_text)->isRequired()->returns(false);
        stub($this->field_int)->isRequired()->returns(false);
        stub($this->field_list)->isRequired()->returns(false);

        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);
        $tracker_factory      = stub('TrackerFactory')->getTrackerById(101)->returns($this->tracker);
        $form_element_factory = stub('Tracker_FormElementFactory')->getUsedFields()->returns($this->used_fields);
        $kanban               = stub('AgileDashboard_Kanban')->getTrackerId()->returns(101);
        stub($this->semantic_title)->getFieldId()->returns(201);

        $checker      = new AgileDashboard_KanbanAddInPlaceChecker($tracker_factory, $form_element_factory);
        $add_in_place = $checker->canUserAddInPlace($this->user, $kanban);

        $this->assertTrue($add_in_place);
    }

    public function itReturnsFalseIfAnotherFieldIsRequired() {
        stub($this->field_string)->isRequired()->returns(true);
        stub($this->field_text)->isRequired()->returns(false);
        stub($this->field_int)->isRequired()->returns(false);
        stub($this->field_list)->isRequired()->returns(true);

        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);
        $tracker_factory      = stub('TrackerFactory')->getTrackerById(101)->returns($this->tracker);
        $form_element_factory = stub('Tracker_FormElementFactory')->getUsedFields()->returns($this->used_fields);
        $kanban               = stub('AgileDashboard_Kanban')->getTrackerId()->returns(101);
        stub($this->semantic_title)->getFieldId()->returns(201);

        $checker      = new AgileDashboard_KanbanAddInPlaceChecker($tracker_factory, $form_element_factory);
        $add_in_place = $checker->canUserAddInPlace($this->user, $kanban);

        $this->assertFalse($add_in_place);
    }

    public function itReturnsFalseIfNoSemanticTitle() {
        stub($this->field_string)->isRequired()->returns(true);
        stub($this->field_text)->isRequired()->returns(false);
        stub($this->field_int)->isRequired()->returns(false);
        stub($this->field_list)->isRequired()->returns(false);

        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);
        $tracker_factory      = stub('TrackerFactory')->getTrackerById(101)->returns($this->tracker);
        $form_element_factory = stub('Tracker_FormElementFactory')->getUsedFields()->returns($this->used_fields);
        $kanban               = stub('AgileDashboard_Kanban')->getTrackerId()->returns(101);
        stub($this->semantic_title)->getFieldId()->returns(null);

        $checker      = new AgileDashboard_KanbanAddInPlaceChecker($tracker_factory, $form_element_factory);
        $add_in_place = $checker->canUserAddInPlace($this->user, $kanban);

        $this->assertFalse($add_in_place);
    }

    public function itReturnsFalseIfTheMandatoryFieldIsNotTheSemanticTitle() {
        stub($this->field_string)->isRequired()->returns(false);
        stub($this->field_text)->isRequired()->returns(false);
        stub($this->field_int)->isRequired()->returns(false);
        stub($this->field_list)->isRequired()->returns(true);

        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);
        $tracker_factory      = stub('TrackerFactory')->getTrackerById(101)->returns($this->tracker);
        $form_element_factory = stub('Tracker_FormElementFactory')->getUsedFields()->returns($this->used_fields);
        $kanban               = stub('AgileDashboard_Kanban')->getTrackerId()->returns(101);
        stub($this->semantic_title)->getFieldId()->returns(201);

        $checker      = new AgileDashboard_KanbanAddInPlaceChecker($tracker_factory, $form_element_factory);
        $add_in_place = $checker->canUserAddInPlace($this->user, $kanban);

        $this->assertFalse($add_in_place);
    }

    public function itReturnsFalseIfTheUserCannotSubmitArtifact() {
        stub($this->field_string)->isRequired()->returns(true);
        stub($this->field_text)->isRequired()->returns(false);
        stub($this->field_int)->isRequired()->returns(false);
        stub($this->field_list)->isRequired()->returns(false);

        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(false);
        $tracker_factory      = stub('TrackerFactory')->getTrackerById(101)->returns($this->tracker);
        $form_element_factory = stub('Tracker_FormElementFactory')->getUsedFields()->returns($this->used_fields);
        $kanban               = stub('AgileDashboard_Kanban')->getTrackerId()->returns(101);
        stub($this->semantic_title)->getFieldId()->returns(201);

        $checker      = new AgileDashboard_KanbanAddInPlaceChecker($tracker_factory, $form_element_factory);
        $add_in_place = $checker->canUserAddInPlace($this->user, $kanban);

        $this->assertFalse($add_in_place);
    }
}