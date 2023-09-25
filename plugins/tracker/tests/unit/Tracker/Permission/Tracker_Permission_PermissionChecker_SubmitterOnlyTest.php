<?php
/**
 * Copyright (c) Enalean, 2013 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types=1);

use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Tracker\Artifact\Artifact;

final class Tracker_Permission_PermissionChecker_SubmitterOnlyTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UserManager
     */
    protected $user_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    protected $tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    protected $user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    protected $submitter;
    /**
     * @var int
     */
    protected $ugroup_id_submitter_only;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Artifact
     */
    protected $artifact;
    /**
     * @var \Mockery\MockInterface|ProjectAccessChecker
     */
    protected $project_access_checker;
    /**
     * @var Tracker_Permission_PermissionChecker
     */
    protected $permission_checker;

    protected function setUp(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getID')->andReturns(120);
        $project->shouldReceive('isPublic')->andReturns(true);

        $this->user_manager           = \Mockery::spy(\UserManager::class);
        $this->project_access_checker = \Mockery::mock(ProjectAccessChecker::class);
        $this->permission_checker     = new Tracker_Permission_PermissionChecker(
            $this->user_manager,
            $this->project_access_checker,
            $this->createMock(\Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker::class),
        );

        $this->tracker = \Mockery::spy(\Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturns(666);
        $this->tracker->shouldReceive('getGroupId')->andReturns(222);
        $this->tracker->shouldReceive('getProject')->andReturns($project);

        $this->ugroup_id_submitter_only = 112;

        $this->user = \Mockery::spy(\PFUser::class);
        $this->user->shouldReceive('getId')->andReturns(120);
        $this->user->shouldReceive('isMember')->with(12)->andReturns(true);

        $this->submitter = \Mockery::spy(\PFUser::class);
        $this->submitter->shouldReceive('getId')->andReturns(250);
        $this->submitter->shouldReceive('isMemberOfUGroup')->with($this->ugroup_id_submitter_only, 222)->andReturns(
            true
        );
        $this->submitter->shouldReceive('isMember')->with(12)->andReturns(true);

        $this->user_manager->shouldReceive('getUserById')->with(120)->andReturns($this->user);
        $this->user_manager->shouldReceive('getUserById')->with(250)->andReturns($this->submitter);

        $this->artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact->shouldReceive('getTracker')->andReturns($this->tracker);

        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')->andReturns(
            [
                Tracker::PERMISSION_SUBMITTER_ONLY => [
                    0 => $this->ugroup_id_submitter_only,
                ],
            ]
        );

        $this->artifact->shouldReceive('getSubmittedBy')->andReturns(250);
    }

    public function testItDoesntSeeArtifactSubmittedByOthers(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->assertFalse($this->permission_checker->userCanView($this->user, $this->artifact));
    }

    public function testItSeesArtifactSubmittedByThemselves(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->assertTrue($this->permission_checker->userCanView($this->submitter, $this->artifact));
    }
}
