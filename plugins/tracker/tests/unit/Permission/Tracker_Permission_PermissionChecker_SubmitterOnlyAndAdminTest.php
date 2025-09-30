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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Permission_PermissionChecker_SubmitterOnlyAndAdminTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    private Tracker_Permission_PermissionChecker $permission_checker;
    private PFUser $user;
    private PFUser $submitter;
    private Artifact&MockObject $artifact;
    private PFUser $restricted_user;
    private PFUser $not_member;
    private Artifact $artifact2;
    private int $ugroup_id_maintainers  = 111;
    private int $ugroup_private_project = 114;
    private PFUser $maintainer;
    private PFUser $tracker_admin;
    private PFUser $project_admin;

    #[\Override]
    protected function setUp(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(222)->build();

        $private_project = ProjectTestBuilder::aProject()->withId(223)->withAccessPrivate()->build();

        $another_project = ProjectTestBuilder::aProject()->withId(12)->build();

        $tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $tracker->method('getId')->willReturn(666);
        $tracker->method('getGroupId')->willReturn(222);
        $tracker->method('getProject')->willReturn($project);
        $tracker->method('isDeleted')->willReturn(false);
        $tracker->method('userIsAdmin')->willReturn(false);

        $ugroup_id_submitter_only = 112;

        $this->user = UserTestBuilder::aUser()
            ->withId(120)
            ->withAdministratorOf($another_project)
            ->withUserGroupMembership($project, $ugroup_id_submitter_only, false)
            ->withUserGroupMembership($project, $this->ugroup_id_maintainers, false)
            ->withoutSiteAdministrator()
            ->build();

        $this->submitter = UserTestBuilder::aUser()
            ->withId(250)
            ->withAdministratorOf($another_project)
            ->withUserGroupMembership($project, $ugroup_id_submitter_only, true)
            ->withUserGroupMembership($project, $this->ugroup_id_maintainers, false)
            ->withoutSiteAdministrator()
            ->build();

        $this->artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact->method('getTracker')->willReturn($tracker);
        $this->artifact->method('useArtifactPermissions')->willReturn(false);
        $this->artifact->method('permission_db_authorized_ugroups')->willReturn(false);

        $tracker->method('getAuthorizedUgroupsByPermissionType')->willReturn(
            [
                Tracker::PERMISSION_SUBMITTER_ONLY => [
                    $ugroup_id_submitter_only,
                ],
                Tracker::PERMISSION_FULL           => [
                    $this->ugroup_id_maintainers,
                ],
                Tracker::PERMISSION_ADMIN          => [
                    ProjectUGroup::PROJECT_ADMIN,
                ],
            ]
        );

        $this->restricted_user = UserTestBuilder::aUser()
            ->withId(249)
            ->withMemberOf($project)
            ->withUserGroupMembership($project, $ugroup_id_submitter_only, false)
            ->withUserGroupMembership($project, $this->ugroup_id_maintainers, false)
            ->withUserGroupMembership($private_project, $this->ugroup_private_project, true)
            ->withoutSiteAdministrator()
            ->withStatus(PFUser::STATUS_RESTRICTED)
            ->build();

        $this->not_member = UserTestBuilder::aUser()
            ->withId(260)
            ->withAdministratorOf($another_project)
            ->withUserGroupMembership($project, $ugroup_id_submitter_only, false)
            ->withUserGroupMembership($project, $this->ugroup_id_maintainers, false)
            ->withUserGroupMembership($private_project, $this->ugroup_private_project, false)
            ->withoutSiteAdministrator()
            ->build();

        $this->maintainer = UserTestBuilder::aUser()
            ->withId(251)
            ->withAdministratorOf($another_project)
            ->withUserGroupMembership($project, $ugroup_id_submitter_only, false)
            ->withUserGroupMembership($project, $this->ugroup_id_maintainers, true)
            ->withoutSiteAdministrator()
            ->build();

        $this->tracker_admin = UserTestBuilder::aUser()
            ->withId(251)
            ->withAdministratorOf($project)
            ->withoutSiteAdministrator()
            ->build();

        $this->project_admin = UserTestBuilder::aUser()
            ->withId(253)
            ->withAdministratorOf($project)
            ->withoutSiteAdministrator()
            ->build();

        $this->artifact->method('getSubmittedBy')->willReturn(250);

        $tracker_in_private_project = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $tracker_in_private_project->method('getId')->willReturn(111);
        $tracker_in_private_project->method('getProject')->willReturn($private_project);
        $tracker_in_private_project->method('getGroupId')->willReturn(223);
        $tracker_in_private_project->method('isDeleted')->willReturn(false);
        $tracker_in_private_project->method('userIsAdmin')->willReturn(false);
        $tracker_in_private_project->method('getAuthorizedUgroupsByPermissionType')->willReturn(
            [
                Tracker::PERMISSION_FULL => [
                    $this->ugroup_private_project,
                ],
            ]
        );

        $project_access_checker = $this->createMock(ProjectAccessChecker::class);
        $project_access_checker->method('checkUserCanAccessProject');

        $this->permission_checker = new Tracker_Permission_PermissionChecker(
            RetrieveUserByIdStub::withUsers($this->user, $this->submitter),
            $project_access_checker,
            $this->createMock(GlobalAdminPermissionsChecker::class),
        );

        $this->artifact2 = \Tuleap\Tracker\Test\Builders\ArtifactTestBuilder::anArtifact(10001)
            ->inTracker($tracker_in_private_project)
            ->build();
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
