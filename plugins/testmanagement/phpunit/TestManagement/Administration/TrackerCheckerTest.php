<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\TestManagement\Administration;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker;
use TrackerFactory;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;

class TrackerCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Tracker */
    private $campaign_tracker;
    /** @var Tracker */
    private $definition_tracker;
    /** @var Tracker */
    private $execution_tracker;
    /** @var Tracker */
    private $issue_tracker;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var TrackerChecker
     */
    private $tracker_checker;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $tracker_from_other_project;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|FrozenFieldsDao
     */
    private $frozen_field_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|HiddenFieldsetsDao
     */
    private $hidden_fieldset_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $deleted_tracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campaign_tracker = Mockery::mock(Tracker::class);
        $this->campaign_tracker->shouldReceive('getId')->andReturn(1);
        $this->definition_tracker = Mockery::mock(Tracker::class);
        $this->definition_tracker->shouldReceive('getId')->andReturn(2);
        $this->execution_tracker = Mockery::mock(Tracker::class);
        $this->execution_tracker->shouldReceive('getId')->andReturn(3);
        $this->issue_tracker = Mockery::mock(Tracker::class);
        $this->issue_tracker->shouldReceive('getId')->andReturn(4);
        $this->tracker_from_other_project = Mockery::mock(Tracker::class);
        $this->tracker_from_other_project->shouldReceive('getId')->andReturn(5);
        $this->deleted_tracker = Mockery::mock(Tracker::class);
        $this->deleted_tracker->shouldReceive('getId')->andReturn(6);

        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive('getID')->andReturn(101);

        $this->tracker_factory = Mockery::mock(\TrackerFactory::class);
        $this->tracker_factory->shouldReceive('getTrackersByGroupId')->with(101)->andReturn([
            $this->campaign_tracker,
            $this->definition_tracker,
            $this->execution_tracker,
            $this->issue_tracker
        ]);

        $this->tracker_factory->shouldReceive('getTrackerById')->with(1)->andReturn($this->campaign_tracker);
        $this->campaign_tracker->shouldReceive('isDeleted')->andReturnFalse();
        $this->tracker_factory->shouldReceive('getTrackerById')->with(2)->andReturn($this->definition_tracker);
        $this->definition_tracker->shouldReceive('isDeleted')->andReturnFalse();
        $this->tracker_factory->shouldReceive('getTrackerById')->with(3)->andReturn($this->execution_tracker);
        $this->execution_tracker->shouldReceive('isDeleted')->andReturnFalse();
        $this->tracker_factory->shouldReceive('getTrackerById')->with(4)->andReturn($this->issue_tracker);
        $this->issue_tracker->shouldReceive('isDeleted')->andReturnFalse();
        $this->tracker_factory->shouldReceive('getTrackerById')->with(5)->andReturn($this->tracker_from_other_project);
        $this->tracker_from_other_project->shouldReceive('isDeleted')->andReturnFalse();
        $this->tracker_factory->shouldReceive('getTrackerById')->with(6)->andReturn($this->deleted_tracker);
        $this->deleted_tracker->shouldReceive('isDeleted')->andReturnTrue();

        $this->frozen_field_dao    = Mockery::mock(FrozenFieldsDao::class);
        $this->hidden_fieldset_dao = Mockery::mock(HiddenFieldsetsDao::class);

        $this->tracker_checker = new TrackerChecker(
            $this->tracker_factory,
            $this->frozen_field_dao,
            $this->hidden_fieldset_dao
        );
    }

    public function testItDoesNotThrowExceptionIfProvidedTrackerIdIsInProject()
    {
        $submitted_id = 1;

        $this->tracker_checker->checkTrackerIsInProject($this->project, $submitted_id);

        $this->addToAssertionCount(1);
    }

    public function testItDoesNotThrowExceptionIfProvidedTrackerIdCanBeUsed()
    {
        $submitted_id = 1;

        $this->frozen_field_dao->shouldReceive('isAFrozenFieldPostActionUsedInTracker')->with(1)->andReturn(false);
        $this->hidden_fieldset_dao->shouldReceive('isAHiddenFieldsetPostActionUsedInTracker')->with(1)->andReturn(false);

        $this->tracker_checker->checkSubmittedTrackerCanBeUsed($this->project, $submitted_id);

        $this->addToAssertionCount(1);
    }

    public function testItThrowsAnExceptionIfProvidedTrackerIdIsNotInProject()
    {
        $submitted_id = 5;
        $this->tracker_factory->shouldReceive('getTrackerById')->with(5);

        $this->frozen_field_dao->shouldReceive('isAFrozenFieldPostActionUsedInTracker')->never();

        $this->expectException(TrackerNotInProjectException::class);
        $this->tracker_checker->checkTrackerIsInProject($this->project, $submitted_id);

        $this->expectException(TrackerNotInProjectException::class);
        $this->tracker_checker->checkSubmittedTrackerCanBeUsed($this->project, $submitted_id);
    }

    public function testItThrowsAnExceptionIfProvidedTrackerDoesntExist()
    {
        $submitted_id = 7;
        $this->tracker_factory->shouldReceive('getTrackerById')->with(7);

        $this->frozen_field_dao->shouldReceive('isAFrozenFieldPostActionUsedInTracker')->never();

        $this->expectException(TrackerDoesntExistException::class);
        $this->tracker_checker->checkTrackerIsInProject($this->project, $submitted_id);

        $this->expectException(TrackerDoesntExistException::class);
        $this->tracker_checker->checkSubmittedTrackerCanBeUsed($this->project, $submitted_id);
    }

    public function testItThrowsAnExceptionIfProvidedTrackerIdIsDeleted()
    {
        $submitted_id = 6;
        $this->tracker_factory->shouldReceive('getTrackerById')->with(6);

        $this->frozen_field_dao->shouldReceive('isAFrozenFieldPostActionUsedInTracker')->never();

        $this->expectException(TrackerIsDeletedException::class);
        $this->tracker_checker->checkTrackerIsInProject($this->project, $submitted_id);

        $this->expectException(TrackerIsDeletedException::class);
        $this->tracker_checker->checkSubmittedTrackerCanBeUsed($this->project, $submitted_id);
    }

    public function testItThrowsAnExceptionIfProvidedTrackerHasAFrozenFieldsPostAction()
    {
        $submitted_id = 1;

        $this->frozen_field_dao->shouldReceive('isAFrozenFieldPostActionUsedInTracker')->with(1)->andReturn(true);

        $this->expectException(TrackerHasAtLeastOneFrozenFieldsPostActionException::class);

        $this->tracker_checker->checkSubmittedTrackerCanBeUsed($this->project, $submitted_id);
    }

    public function testItThrowsAnExceptionIfProvidedTrackerHasAHiddenFieldsetPostAction()
    {
        $submitted_id = 1;

        $this->frozen_field_dao->shouldReceive('isAFrozenFieldPostActionUsedInTracker')->with(1)->andReturn(false);
        $this->hidden_fieldset_dao->shouldReceive('isAHiddenFieldsetPostActionUsedInTracker')->with(1)->andReturn(true);

        $this->expectException(TrackerHasAtLeastOneHiddenFieldsetsPostActionException::class);

        $this->tracker_checker->checkSubmittedTrackerCanBeUsed($this->project, $submitted_id);
    }
}
