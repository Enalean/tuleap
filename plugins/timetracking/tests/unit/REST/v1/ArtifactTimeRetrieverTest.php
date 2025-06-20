<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Timetracking\Admin\TimetrackingEnabler;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;
use Tuleap\Timetracking\REST\v1\Exception\ArtifactDoesNotExistException;
use Tuleap\Timetracking\REST\v1\Exception\ArtifactIDMissingException;
use Tuleap\Timetracking\REST\v1\Exception\NoTimetrackingForTrackerException;
use Tuleap\Timetracking\REST\v1\Exception\UserCannotSeeTrackedTimeException;
use Tuleap\Timetracking\Time\Time;
use Tuleap\Timetracking\Time\TimeRetriever;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactTimeRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TimeRetriever
     */
    private $time_retriever;
    /**
     * @var PermissionsRetriever&\PHPUnit\Framework\MockObject\MockObject
     */
    private $permissions_retriever;
    /**
     * @var TimetrackingEnabler&\PHPUnit\Framework\MockObject\MockObject
     */
    private $timetracking_enabler;
    /**
     * @var ProjectStatusVerificator&\PHPUnit\Framework\MockObject\MockObject
     */
    private $project_verificator;
    /**
     * @var \Project&\PHPUnit\Framework\MockObject\MockObject
     */
    private $project;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\Tracker\Tracker
     */
    private $tracker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\PFUser
     */
    private $user;
    private ArtifactTimeRetriever $retriever;

    protected function setUp(): void
    {
        parent::setUp();
        $this->time_retriever        = $this->createMock(TimeRetriever::class);
        $this->permissions_retriever = $this->createMock(PermissionsRetriever::class);
        $this->timetracking_enabler  = $this->createMock(TimetrackingEnabler::class);
        $this->project_verificator   = $this->createMock(ProjectStatusVerificator::class);
        $this->project               = $this->createMock(\Project::class);
        $this->tracker               = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $this->artifact              = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact_factory      = $this->createMock(\Tracker_ArtifactFactory::class);
        $this->user                  = $this->createMock(\PFUser::class);
        $this->retriever             = new ArtifactTimeRetriever(
            $this->artifact_factory,
            $this->project_verificator,
            $this->timetracking_enabler,
            $this->permissions_retriever,
            $this->time_retriever
        );

        $this->tracker->method('getProject')->willReturn($this->project);
        $this->artifact->method('getTracker')->willReturn($this->tracker);
    }

    public function testItRaisesAnExceptionWhenQueryIsEmpty(): void
    {
        $this->expectException(ArtifactIDMissingException::class);
        $this->retriever->getArtifactTime($this->user, '');
    }

    public function testItRaisesAnExceptionWhenQueryIsNotValidJSON(): void
    {
        $this->expectException(ArtifactIDMissingException::class);
        $this->retriever->getArtifactTime($this->user, 'artifact id => 12');
    }

    public function testItRaisesAnExceptionWhenArtifactDoesntExist(): void
    {
        $this->artifact_factory->method('getArtifactByIdUserCanView')->with($this->user, 12)->willReturn(null);
        $this->expectException(ArtifactDoesNotExistException::class);
        $this->retriever->getArtifactTime($this->user, '{"artifact_id": "12"}');
    }

    public function testItRaisesAnExceptionWhenArtifactBelongsToAnInvalidProject(): void
    {
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($this->artifact);

        $this->project_verificator->method('checkProjectStatusAllowsAllUsersToAccessIt')->with($this->project)->willThrowException(new RestException(403));
        $this->expectException(RestException::class);

        $this->retriever->getArtifactTime($this->user, '{"artifact_id": "12"}');
    }

    public function testItRaisesAnExceptionWhenTrackerDoesntHaveTimeTracking(): void
    {
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($this->artifact);
        $this->project_verificator->method('checkProjectStatusAllowsAllUsersToAccessIt');

        $this->timetracking_enabler->method('isTimetrackingEnabledForTracker')->with($this->tracker)->willReturn(false);
        $this->expectException(NoTimetrackingForTrackerException::class);

        $this->retriever->getArtifactTime($this->user, '{"artifact_id": "12"}');
    }

    public function testItRaisesAnExceptionWhenUserCannotSeeAggregatedTimesOnTracker(): void
    {
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($this->artifact);
        $this->project_verificator->method('checkProjectStatusAllowsAllUsersToAccessIt');
        $this->timetracking_enabler->method('isTimetrackingEnabledForTracker')->with($this->tracker)->willReturn(true);

        $this->permissions_retriever->method('userCanSeeAllTimesInTracker')->willReturn(false);
        $this->expectException(UserCannotSeeTrackedTimeException::class);

        $this->retriever->getArtifactTime($this->user, '{"artifact_id": "12"}');
    }

    public function testItReturnsACollectionOfTimes(): void
    {
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($this->artifact);
        $this->project_verificator->method('checkProjectStatusAllowsAllUsersToAccessIt');
        $this->timetracking_enabler->method('isTimetrackingEnabledForTracker')->with($this->tracker)->willReturn(true);
        $this->permissions_retriever->method('userCanSeeAllTimesInTracker')->willReturn(true);

        $times_for_user = [
            new Time(9000, 120, 12, '2018-12-12', 45, 'Write some tests'),
        ];
        $this->time_retriever->method('getTimesForUser')->with($this->user, $this->artifact)->willReturn($times_for_user);

        $times = $this->retriever->getArtifactTime($this->user, '{"artifact_id": "12"}');
        self::assertEquals(45, $times[0]->minutes);
    }
}
