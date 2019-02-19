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

namespace Tuleap\Baseline;

require_once __DIR__ . '/../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker;
use Tracker_Artifact;
use Tuleap\Baseline\Support\DateTimeFactory;
use Tuleap\GlobalLanguageMock;

class PermissionsImplTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var Permissions
     */
    private $permissions;

    /** @var CurrentUserProvider|MockInterface */
    private $current_user_provider;

    /**
     * @var ProjectPermissions|MockInterface
     */
    private $project_permissions;

    /**
     * @before
     */
    public function createInstance()
    {
        $this->current_user_provider = Mockery::mock(CurrentUserProvider::class)
            ->shouldReceive('getUser')
            ->andReturn(new PFUser())
            ->getMock();
        $this->project_permissions   = Mockery::mock(ProjectPermissions::class);

        $this->permissions = new PermissionsImpl(
            $this->current_user_provider,
            $this->project_permissions
        );
    }

    public function testCheckReadSimpleBaselineDoesNotThrownAnyExeptionWhenPermissionGranted()
    {
        $milestone = $this->mockAMilestone();
        $baseline  = $this->buildASimpleBaseline($milestone);

        $milestone->shouldReceive('userCanView')->andReturn(true);
        $milestone->getTracker()->shouldReceive('userCanView')->andReturn(true);
        $this->project_permissions->shouldReceive('checkRead');

        $this->permissions->checkReadSimpleBaseline($baseline);
    }

    public function testCheckReadSimpleBaselineThrowsWhenUserCannotViewGivenArtifact()
    {
        $this->expectException(NotAuthorizedException::class);

        $milestone = $this->mockAMilestone();
        $baseline  = $this->buildASimpleBaseline($milestone);

        $milestone->shouldReceive('userCanView')
            ->andReturn(false);

        $this->permissions->checkReadSimpleBaseline($baseline);
    }

    public function testCheckReadSimpleBaselineThrowsWhenUserCannotViewTrackerOfGivenArtifact()
    {
        $this->expectException(NotAuthorizedException::class);

        $milestone = $this->mockAMilestone();
        $baseline  = $this->buildASimpleBaseline($milestone);

        $milestone->shouldReceive('userCanView')
            ->andReturn(true);
        $milestone->getTracker()->shouldReceive('userCanView')
            ->andReturn(false);

        $this->permissions->checkReadSimpleBaseline($baseline);
    }

    /**
     * @return Tracker_Artifact|MockInterface
     */
    private function mockAMilestone()
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

    private function buildASimpleBaseline(Tracker_Artifact $milestone): SimplifiedBaseline
    {
        return new SimplifiedBaseline(
            $milestone,
            'title',
            'description',
            'status',
            DateTimeFactory::one()
        );
    }
}
