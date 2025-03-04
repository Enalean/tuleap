<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Project\Sidebar;

use PHPUnit\Framework\Attributes\DataProvider;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\CheckProjectAccess;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CheckProjectAccessStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LinkedProjectTest extends TestCase
{
    use GlobalLanguageMock;

    private \Project $project;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()
            ->withUnixName('red-team')
            ->withPublicName('Red Team')
            ->build();
        $this->user    = UserTestBuilder::aUser()->build();
    }

    public function testItBuildsFromProject(): void
    {
        $linked_project = LinkedProject::fromProject(
            CheckProjectAccessStub::withValidAccess(),
            $this->project,
            $this->user
        );
        self::assertSame('Red Team', $linked_project->public_name);
        self::assertSame('/projects/red-team', $linked_project->uri);
    }

    public static function dataProviderAccessExceptions(): array
    {
        return [
            'with invalid project'                           => [CheckProjectAccessStub::withNotValidProject()],
            'with suspended project'                         => [CheckProjectAccessStub::withSuspendedProject()],
            'with deleted project'                           => [CheckProjectAccessStub::withDeletedProject()],
            'with user restricted without access to project' => [CheckProjectAccessStub::withRestrictedUserWithoutAccess()],
            'with private project and user not member'       => [CheckProjectAccessStub::withPrivateProjectWithoutAccess()],
        ];
    }

    #[DataProvider('dataProviderAccessExceptions')]
    public function testItReturnsNullWhenUserCannotSeeProject(CheckProjectAccess $access_checker): void
    {
        self::assertNull(LinkedProject::fromProject($access_checker, $this->project, $this->user));
    }
}
