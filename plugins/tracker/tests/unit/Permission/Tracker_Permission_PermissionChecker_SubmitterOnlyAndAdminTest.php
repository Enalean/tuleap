<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

final class Tracker_Permission_PermissionChecker_SubmitterOnlyAndAdminTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Tracker_Permission_PermissionChecker
     */
    private $permission_checker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $submitter;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Artifact
     */
    private $artifact;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $restricted_user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $not_member;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Artifact
     */
    private $artifact2;
    /**
     * @var int
     */
    private $ugroup_id_maintainers = 111;
    /**
     * @var int
     */
    private $ugroup_id_admin = 4;
    /**
     * @var int
     */
    private $ugroup_private_project = 114;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $maintainer;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $tracker_admin;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $project_admin;

    protected function setUp(): void
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getID')->andReturns(120);
        $project->shouldReceive('isPublic')->andReturns(true);

        $user_manager             = \Mockery::spy(\UserManager::class);
        $project_access_checker   = \Mockery::spy(ProjectAccessChecker::class);
        $this->permission_checker = new Tracker_Permission_PermissionChecker(
            $user_manager,
            $project_access_checker,
            $this->createMock(\Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker::class),
        );

        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturns(666);
        $tracker->shouldReceive('getGroupId')->andReturns(222);
        $tracker->shouldReceive('getProject')->andReturns($project);

        $ugroup_id_submitter_only = 112;

        $this->user = \Mockery::spy(\PFUser::class);
        $this->user->shouldReceive('getId')->andReturns(120);
        $this->user->shouldReceive('isMember')->with(12)->andReturns(true);
        $this->user->shouldReceive('userIsAdmin')->with(12)->andReturns(true);
        $this->user->shouldReceive('isAdmin')->andReturns(false);

        $this->submitter = \Mockery::spy(\PFUser::class);
        $this->submitter->shouldReceive('getId')->andReturns(250);
        $this->submitter->shouldReceive('isAdmin')->andReturns(false);
        $this->submitter->shouldReceive('isMemberOfUGroup')->with($ugroup_id_submitter_only, 222)->andReturns(
            true
        );
        $this->submitter->shouldReceive('isMember')->with(12)->andReturns(true);

        $user_manager->shouldReceive('getUserById')->with(120)->andReturns($this->user);
        $user_manager->shouldReceive('getUserById')->with(250)->andReturns($this->submitter);

        $this->artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact->shouldReceive('getTracker')->andReturns($tracker);
        $this->artifact->shouldReceive('useArtifactPermissions')->andReturnFalse();
        $this->artifact->shouldReceive('permission_db_authorized_ugroups')->andReturnFalse();

        $tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')->andReturns(
            [
                Tracker::PERMISSION_SUBMITTER_ONLY => [
                    $ugroup_id_submitter_only,
                ],
                Tracker::PERMISSION_FULL           => [
                    $this->ugroup_id_maintainers,
                ],
                Tracker::PERMISSION_ADMIN          => [
                    $this->ugroup_id_admin,
                ],
            ]
        );

        $this->restricted_user = \Mockery::spy(\PFUser::class);
        $this->restricted_user->shouldReceive('getId')->andReturns(249);
        $this->restricted_user->shouldReceive('isMemberOfUGroup')->with(114, 223)->andReturns(true);
        $this->restricted_user->shouldReceive('isSuperUser')->andReturns(false);
        $this->restricted_user->shouldReceive('isMember')->with(223)->andReturns(true);
        $this->restricted_user->shouldReceive('isMember')->with(222)->andReturns(false);
        $this->restricted_user->shouldReceive('isRestricted')->andReturns(true);
        $this->restricted_user->shouldReceive('isAdmin')->andReturns(false);

        $this->not_member = \Mockery::spy(\PFUser::class);
        $this->not_member->shouldReceive('getId')->andReturns(250);
        $this->not_member->shouldReceive('isMemberOfUGroup')->andReturns(false);
        $this->not_member->shouldReceive('isSuperUser')->andReturns(false);
        $this->not_member->shouldReceive('isMember')->andReturns(false);
        $this->not_member->shouldReceive('isRestricted')->andReturns(false);
        $this->not_member->shouldReceive('isAdmin')->andReturns(false);

        $this->maintainer = \Mockery::spy(\PFUser::class);
        $this->maintainer->shouldReceive('getId')->andReturns(251);
        $this->maintainer->shouldReceive('isAdmin')->andReturns(false);
        $this->maintainer->shouldReceive('isMemberOfUGroup')->with($this->ugroup_id_maintainers, 222)->andReturns(true);

        $this->tracker_admin = \Mockery::spy(\PFUser::class);
        $this->tracker_admin->shouldReceive('isAdmin')->andReturnTrue();
        $tracker->shouldReceive('userIsAdmin')->andReturns(false);
        $tracker->shouldReceive('isAdmin')->andReturns(false);

        $this->project_admin = \Mockery::spy(\PFUser::class);
        $this->project_admin->shouldReceive('getId')->andReturns(253);
        $this->project_admin->shouldReceive('isAdmin')->with(120)->andReturns(true);
        $this->project_admin->shouldReceive('isAdmin')->andReturns(false);

        $this->artifact->shouldReceive('getSubmittedBy')->andReturns(250);

        $private_project = Mockery::spy(\Project::class);
        $private_project->shouldReceive('isPublic')->andReturnFalse();

        $tracker_in_private_project = Mockery::spy(\Tracker::class);
        $tracker_in_private_project->shouldReceive('getProject')->andReturns($private_project);

        $private_project->shouldReceive('getID')->andReturns(223);
        $tracker_in_private_project->shouldReceive('getGroupId')->andReturns(223);
        $tracker_in_private_project->shouldReceive('getAuthorizedUgroupsByPermissionType')->andReturns(
            [
                Tracker::PERMISSION_FULL => [
                    $this->ugroup_private_project,
                ],
            ]
        );

        $project_access_checker->shouldReceive('checkUserCanAccessProject');

        $this->artifact2 = Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact2->shouldReceive('getTracker')->andReturns($tracker_in_private_project);
    }

    public function testItDoesntSeeArtifactSubmittedByOthers(): void
    {
        $this->assertFalse($this->permission_checker->userCanView($this->user, $this->artifact));
    }

    public function testItSeesArtifactSubmittedByThemselves(): void
    {
        $this->assertTrue($this->permission_checker->userCanView($this->submitter, $this->artifact));
    }

    public function testItSeesArtifactBecauseHeIsGrantedFullAccess(): void
    {
        $this->assertTrue($this->permission_checker->userCanView($this->maintainer, $this->artifact));
    }

    public function testItSeesArtifactBecauseHeIsTrackerAdmin(): void
    {
        $this->assertTrue($this->permission_checker->userCanView($this->tracker_admin, $this->artifact));
    }

    public function testItSeesArtifactBecauseHeIsProjectAdmin(): void
    {
        $this->assertTrue($this->permission_checker->userCanView($this->project_admin, $this->artifact));
    }

    public function testItDoesNotSeeArtifactBecauseHeIsRestricted(): void
    {
        $this->assertFalse($this->permission_checker->userCanView($this->restricted_user, $this->artifact));
    }

    public function testItSeesTheArtifactBecauseHeIsRestrictedAndProjectMember(): void
    {
        $this->assertTrue($this->permission_checker->userCanView($this->restricted_user, $this->artifact2));
    }

    public function testItDoesNotSeeArtifactBecauseHeIsNotProjectMemberOfAPrivateProject(): void
    {
        $this->assertFalse($this->permission_checker->userCanView($this->not_member, $this->artifact2));
    }
}
