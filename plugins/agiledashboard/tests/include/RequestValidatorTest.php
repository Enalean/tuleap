<?php
/**
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
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

require_once dirname(__FILE__).'/../bootstrap.php';

class RequestValidatorTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->factory   = mock('PlanningFactory');
        $this->validator = new Planning_RequestValidator($this->factory);
    }
}

class RequestValidator_MissingParameterTest extends RequestValidatorTest
{

    public function itRejectsTheRequestWhenNameIsMissing()
    {
        $request = aPlanningCreationRequest()->withPlanningName(null)->build();
        $this->assertFalse($this->validator->isValid($request));
    }

    public function itRejectsTheRequestWhenBacklogTrackerIdsAreMissing()
    {
        $request = aPlanningCreationRequest()->withBacklogTrackerId(null)->build();
        $this->assertFalse($this->validator->isValid($request));
    }

    public function itRejectsTheRequestWhenPlanningTrackerIdIsMissing()
    {
        $request = aPlanningCreationRequest()->withPlanningTrackerId(null)->build();
        $this->assertFalse($this->validator->isValid($request));
    }
}

class RequestValidator_NoMissingParameterTest extends RequestValidatorTest
{

    public function setUp()
    {
        parent::setUp();

        $this->group_id            = 12;
        $this->release_planning_id = 34;
        $this->releases_tracker_id = 56;
        $this->sprints_tracker_id  = 78;
        $this->holidays_tracker_id = 90;

        $this->release_planning = aPlanning()->withId($this->release_planning_id)
                                             ->withPlanningTrackerId($this->releases_tracker_id)
                                             ->build();

        stub($this->factory)->getPlanning($this->release_planning_id)
                            ->returns($this->release_planning);
        stub($this->factory)->getPlanningTrackerIdsByGroupId($this->group_id)
                            ->returns(array($this->releases_tracker_id,
                                            $this->sprints_tracker_id));
    }

    private function aRequest()
    {
        return aPlanningCreationRequest()->withGroupId($this->group_id)
                                         ->withPlanningId($this->release_planning_id);
    }

    public function itValidatesTheRequestWhenPlanningTrackerIsNotUsedInAPlanningOfTheSameProject()
    {
        $request = $this->aRequest()->withPlanningTrackerId($this->holidays_tracker_id)->build();

        $this->assertTrue($this->validator->isValid($request));
    }

    public function itValidatesTheRequestWhenPlanningTrackerIsTheCurrentOne()
    {
        $request = $this->aRequest()->withPlanningTrackerId($this->releases_tracker_id)->build();

        $this->assertTrue($this->validator->isValid($request));
    }

    public function itRejectsTheRequestWhenPlanningTrackerIsUsedInAPlanningOfTheSameProject()
    {
        $request = $this->aRequest()->withPlanningTrackerId($this->sprints_tracker_id)->build();

        $this->assertFalse($this->validator->isValid($request));
    }
}
