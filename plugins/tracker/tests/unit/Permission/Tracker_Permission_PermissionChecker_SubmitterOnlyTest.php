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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Permission_PermissionChecker_SubmitterOnlyTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    protected PFUser&MockObject $user;
    protected PFUser&MockObject $submitter;
    protected int $ugroup_id_submitter_only = 112;
    protected Artifact $artifact;
    protected ProjectAccessChecker&MockObject $project_access_checker;
    protected Tracker_Permission_PermissionChecker $permission_checker;

    #[\Override]
    protected function setUp(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(222)->withAccessPublic()->build();

        $this->user = $this->createMock(\PFUser::class);
        $this->user->method('getId')->willReturn(120);
        $this->user->method('isAdmin')->willReturn(false);
        $this->user->method('isMemberOfUGroup')->willReturn(false);

        $this->submitter = $this->createMock(\PFUser::class);
        $this->submitter->method('getId')->willReturn(250);
        $this->submitter->method('isAdmin')->willReturn(false);
        $this->submitter->method('isMemberOfUGroup')
            ->with($this->ugroup_id_submitter_only, $project->getID())
            ->willReturn(true);

        $this->project_access_checker = $this->createMock(ProjectAccessChecker::class);
        $this->project_access_checker->method('checkUserCanAccessProject');

        $this->permission_checker = new Tracker_Permission_PermissionChecker(
            RetrieveUserByIdStub::withUsers($this->user, $this->submitter),
            $this->project_access_checker,
            $this->createMock(\Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker::class),
        );

        $tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $tracker->method('getId')->willReturn(666);
        $tracker->method('getGroupId')->willReturn($project->getID());
        $tracker->method('getProject')->willReturn($project);
        $tracker->method('isDeleted')->willReturn(false);
        $tracker->method('userIsAdmin')->willReturn(false);
        $tracker->method('getAuthorizedUgroupsByPermissionType')->willReturn(
            [
                Tracker::PERMISSION_SUBMITTER_ONLY => [
                    0 => $this->ugroup_id_submitter_only,
                ],
            ]
        );

        $this->artifact = ArtifactTestBuilder::anArtifact(250)
            ->inTracker($tracker)
            ->submittedBy($this->submitter)
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
}
