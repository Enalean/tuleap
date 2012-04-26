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

require_once dirname(__FILE__).'/../../include/Planning/RequestValidator.class.php';

class TestPlanningCreationRequestBuilder {
    public function __construct() {
        $this->group_id            = '123';
        $this->planning_name       = 'My Planning';
        $this->planning_tracker_id = '1';
        $this->backlog_tracker_ids = array('2', '3');
    }
    
    public function withGroupId($group_id) {
        $this->group_id = $group_id;
        return $this;
    }
    
    public function withPlanningName($planning_name) {
        $this->planning_name = $planning_name;
        return $this;
    }
    
    public function withBacklogTrackerIds($backlog_tracker_ids) {
        $this->backlog_tracker_ids = $backlog_tracker_ids;
        return $this;
    }
    
    public function withPlanningTrackerId($planning_tracker_id) {
        $this->planning_tracker_id = $planning_tracker_id;
        return $this;
    }
    
    public function build() {
        return new Codendi_Request(array(
            'group_id'            => $this->group_id,
            'planning_name'       => $this->planning_name,
            'planning_tracker_id' => $this->planning_tracker_id,
            'backlog_tracker_ids' => $this->backlog_tracker_ids
        ));
    }
}

// TODO: extract to a separate file
function aPlanningCreationRequest() {
    return new TestPlanningCreationRequestBuilder();
}

class RequestValidatorTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $this->factory   = mock('PlanningFactory');
        $this->validator = new Planning_RequestValidator($this->factory);
    }
}

class RequestValidator_MissingParameterTest extends RequestValidatorTest {
    
    public function itRejectsTheRequestWhenNameIsMissing() {
        $request = aPlanningCreationRequest()->withPlanningName(null)->build();
        $this->assertFalse($this->validator->isValid($request));
    }
    
    public function itRejectsTheRequestWhenBacklogTrackerIdsAreMissing() {
        $request = aPlanningCreationRequest()->withBacklogTrackerIds(null)->build();
        $this->assertFalse($this->validator->isValid($request));
    }
    
    public function itRejectsTheRequestWhenPlanningTrackerIdIsMissing() {
        $request = aPlanningCreationRequest()->withPlanningTrackerId(null)->build();
        $this->assertFalse($this->validator->isValid($request));
    }
}

class RequestValidator_NoMissingParameterTest extends RequestValidatorTest {
    
    public function setUp() {
        parent::setUp();
        
        $this->group_id            = 45;
        $this->planning_tracker_id = 67;
        
        $this->request = aPlanningCreationRequest()->withGroupId($this->group_id)
                                                   ->withPlanningTrackerId($this->planning_tracker_id)
                                                   ->build();
    }
    
    public function itValidatesTheRequestWhenPlanningTrackerIsNotUsedInAPlanningOfTheSameProject() {
        stub($this->factory)
            ->getPlanningTrackerIdsByGroupId($this->group_id)
            ->returns(array());
        
        $this->assertTrue($this->validator->isValid($this->request));
    }
    
    public function itRejectsTheRequestWhenPlanningTrackerIsUsedInAPlanningOfTheSameProject() {
        stub($this->factory)
            ->getPlanningTrackerIdsByGroupId($this->group_id)
            ->returns(array($this->planning_tracker_id));
        
        $this->assertFalse($this->validator->isValid($this->request));
    }
}
?>
