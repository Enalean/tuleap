<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Baseline\REST;

require_once __DIR__ . '/../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker;
use Tracker_Artifact;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\REST\UserManager;

class ArtifactPermissionsCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var UserManager|MockInterface
     */
    private $user_manager;

    /**
     * @var ProjectStatusVerificator|MockInterface
     */
    private $project_status_verificator;

    /**
     * @var ArtifactPermissionsChecker
     */
    private $artifact_permissions_checker;

    /**
     * @before
     */
    public function createInstance()
    {
        $this->user_manager               = Mockery::mock(UserManager::class);
        $this->project_status_verificator = Mockery::mock(ProjectStatusVerificator::class);

        $this->artifact_permissions_checker = new ArtifactPermissionsChecker(
            $this->user_manager,
            $this->project_status_verificator
        );

        $current_user = Mockery::mock(PFUser::class);
        $this->user_manager->shouldReceive('getCurrentUser')
            ->andReturn($current_user);
    }

    public function testCheckRead()
    {
        $artifact = $this->mockAnArtifact();

        $artifact->shouldReceive('userCanView')
            ->andReturn(true);
        $artifact->getTracker()->shouldReceive('userCanView')
            ->andReturn(true);
        $this->project_status_verificator->shouldReceive('checkProjectStatusAllowsAllUsersToAccessIt');

        $this->artifact_permissions_checker->checkRead($artifact);
    }

    public function testCheckReadThrowsWhenUserCannotViewGivenArtifact()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(403);

        $artifact = $this->mockAnArtifact();

        $artifact->shouldReceive('userCanView')
            ->andReturn(false);

        $this->artifact_permissions_checker->checkRead($artifact);
    }


    public function testCheckReadThrowsWhenUserCannotViewTrackerOfGivenArtifact()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(403);

        $artifact = $this->mockAnArtifact();

        $artifact->shouldReceive('userCanView')
            ->andReturn(true);
        $artifact->getTracker()->shouldReceive('userCanView')
            ->andReturn(false);

        $this->artifact_permissions_checker->checkRead($artifact);
    }

    /**
     * @return Tracker_Artifact|MockInterface
     */
    private function mockAnArtifact()
    {
        $project = Mockery::mock(Project::class);
        $tracker = Mockery::mock(Tracker::class)
            ->shouldReceive('getProject')
            ->andReturn($project)
            ->getMock();
        return Mockery::mock(Tracker_Artifact::class)
            ->shouldReceive('getTracker')
            ->andReturn($tracker)
            ->getMock();
    }
}
