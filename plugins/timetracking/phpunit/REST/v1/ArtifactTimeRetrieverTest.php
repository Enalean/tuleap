<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Timetracking\REST\v1;

use Luracast\Restler\RestException;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Timetracking\Admin\TimetrackingEnabler;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;
use Tuleap\Timetracking\REST\v1\Exception\ArtifactDoesNotExistException;
use Tuleap\Timetracking\REST\v1\Exception\ArtifactIDMissingException;
use Tuleap\Timetracking\REST\v1\Exception\NoTimetrackingForTrackerException;
use Tuleap\Timetracking\REST\v1\Exception\UserCannotSeeTrackedTimeException;
use Tuleap\Timetracking\Time\Time;
use Tuleap\Timetracking\Time\TimeRetriever;

class ArtifactTimeRetrieverTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var ArtifactTimeRetriever
     */
    private $retriever;
    /**
     * @var M\MockInterface|\PFUser
     */
    private $user;
    /**
     * @var M\MockInterface|\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var M\MockInterface|\Tracker_Artifact
     */
    private $artifact;
    /**
     * @var M\MockInterface|ProjectStatusVerificator
     */
    private $project_verificator;
    /**
     * @var M\MockInterface|TimetrackingEnabler
     */
    private $timetracking_enabler;
    /**
     * @var M\MockInterface|\Tracker
     */
    private $tracker;
    /**
     * @var M\MockInterface|\Project
     */
    private $project;
    /**
     * @var M\MockInterface|PermissionsRetriever
     */
    private $permissions_retriever;
    /**
     * @var M\MockInterface|TimeRetriever
     */
    private $time_retriever;

    protected function setUp(): void
    {
        parent::setUp();
        $this->time_retriever        = M::mock(TimeRetriever::class);
        $this->permissions_retriever = M::mock(PermissionsRetriever::class);
        $this->timetracking_enabler  = M::mock(TimetrackingEnabler::class);
        $this->project_verificator   = M::mock(ProjectStatusVerificator::class);
        $this->project               = M::mock(\Project::class);
        $this->tracker               = M::mock(\Tracker::class, ['getProject' => $this->project]);
        $this->artifact              = M::mock(\Tracker_Artifact::class, ['getTracker' => $this->tracker]);
        $this->artifact_factory      = M::mock(\Tracker_ArtifactFactory::class);
        $this->user                  = M::mock(\PFUser::class);
        $this->retriever             = new ArtifactTimeRetriever(
            $this->artifact_factory,
            $this->project_verificator,
            $this->timetracking_enabler,
            $this->permissions_retriever,
            $this->time_retriever
        );
    }

    public function testItRaisesAnExceptionWhenQueryIsEmpty()
    {
        $this->expectException(ArtifactIDMissingException::class);
        $this->retriever->getArtifactTime($this->user, '');
    }

    public function testItRaisesAnExceptionWhenQueryIsNotValidJSON()
    {
        $this->expectException(ArtifactIDMissingException::class);
        $this->retriever->getArtifactTime($this->user, 'artifact id => 12');
    }

    public function testItRaisesAnExceptionWhenArtifactDoesntExist()
    {
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($this->user, 12)->andReturns(null);
        $this->expectException(ArtifactDoesNotExistException::class);
        $this->retriever->getArtifactTime($this->user, '{"artifact_id": "12"}');
    }

    public function testItRaisesAnExceptionWhenArtifactBelongsToAnInvalidProject()
    {
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->andReturns($this->artifact);

        $this->project_verificator->shouldReceive('checkProjectStatusAllowsAllUsersToAccessIt')->with($this->project)->andThrow(new RestException(403));
        $this->expectException(RestException::class);

        $this->retriever->getArtifactTime($this->user, '{"artifact_id": "12"}');
    }

    public function testItRaisesAnExceptionWhenTrackerDoesntHaveTimeTracking()
    {
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->andReturns($this->artifact);
        $this->project_verificator->shouldReceive('checkProjectStatusAllowsAllUsersToAccessIt');

        $this->timetracking_enabler->shouldReceive('isTimetrackingEnabledForTracker')->with($this->tracker)->andReturns(false);
        $this->expectException(NoTimetrackingForTrackerException::class);

        $this->retriever->getArtifactTime($this->user, '{"artifact_id": "12"}');
    }

    public function testItRaisesAnExceptionWhenUserCannotSeeAggregatedTimesOnTracker()
    {
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->andReturns($this->artifact);
        $this->project_verificator->shouldReceive('checkProjectStatusAllowsAllUsersToAccessIt');
        $this->timetracking_enabler->shouldReceive('isTimetrackingEnabledForTracker')->with($this->tracker)->andReturns(true);

        $this->permissions_retriever->shouldReceive('userCanSeeAggregatedTimesInTracker')->andReturn(false);
        $this->expectException(UserCannotSeeTrackedTimeException::class);

        $this->retriever->getArtifactTime($this->user, '{"artifact_id": "12"}');
    }

    public function testItReturnsACollectionOfTimes()
    {
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->andReturns($this->artifact);
        $this->project_verificator->shouldReceive('checkProjectStatusAllowsAllUsersToAccessIt');
        $this->timetracking_enabler->shouldReceive('isTimetrackingEnabledForTracker')->with($this->tracker)->andReturns(true);
        $this->permissions_retriever->shouldReceive('userCanSeeAggregatedTimesInTracker')->andReturn(true);

        $times_for_user = [
            new Time(9000, 120, 12, '2018-12-12', 45, 'Write some tests'),
        ];
        $this->time_retriever->shouldReceive('getTimesForUser')->with($this->user, $this->artifact)->andReturns($times_for_user);

        $times = $this->retriever->getArtifactTime($this->user, '{"artifact_id": "12"}');
        $this->assertEquals(45, $times[0]->minutes);
    }
}
