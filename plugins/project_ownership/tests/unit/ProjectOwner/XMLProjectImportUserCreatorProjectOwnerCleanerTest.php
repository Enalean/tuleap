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

namespace Tuleap\ProjectOwnership\ProjectOwner;

use PFUser;
use Project;
use ProjectUGroup;
use Tuleap\Project\Admin\ProjectUGroup\ProjectImportCleanupUserCreatorFromAdministrators;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XMLProjectImportUserCreatorProjectOwnerCleanerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testFirstProjectAdministratorFoundNotBeingTheCreatorIsSetAsProjectOwner(): void
    {
        $updater = $this->createMock(ProjectOwnerUpdater::class);
        $cleaner = new XMLProjectImportUserCreatorProjectOwnerCleaner($updater);

        $creator = $this->createMock(PFUser::class);
        $creator->method('getId')->willReturn(101);
        $other_admin_1 = $this->createMock(PFUser::class);
        $other_admin_1->method('getId')->willReturn(102);
        $other_admin_2 = $this->createMock(PFUser::class);

        $ugroup  = $this->createUGroupAdmin();
        $project = $this->createMock(Project::class);
        $ugroup->method('getProject')->willReturn($project);
        $ugroup->method('getMembers')->willReturn([$creator, $other_admin_1, $other_admin_2]);

        $updater->expects($this->atLeastOnce())->method('updateProjectOwner')->with($project, $other_admin_1);

        $cleaner->updateProjectOwnership($this->createEvent($creator, $ugroup));
    }

    public function testNothingIsDoneWhenTheUGroupIsCorruptedAndCannotFindTheProjec(): void
    {
        $updater = $this->createMock(ProjectOwnerUpdater::class);
        $cleaner = new XMLProjectImportUserCreatorProjectOwnerCleaner($updater);

        $creator = $this->createMock(PFUser::class);

        $ugroup = $this->createUGroupAdmin();
        $ugroup->method('getProject')->willReturn(null);

        $updater->expects($this->never())->method('updateProjectOwner');

        $cleaner->updateProjectOwnership($this->createEvent($creator, $ugroup));
    }

    private function createEvent(PFUser $creator, ProjectUGroup $ugroup): ProjectImportCleanupUserCreatorFromAdministrators
    {
        return new ProjectImportCleanupUserCreatorFromAdministrators($creator, $ugroup);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject&ProjectUGroup
     */
    private function createUGroupAdmin(): ProjectUGroup
    {
        $ugroup = $this->createMock(ProjectUGroup::class);
        $ugroup->method('getId')->willReturn(ProjectUGroup::PROJECT_ADMIN);

        return $ugroup;
    }
}
