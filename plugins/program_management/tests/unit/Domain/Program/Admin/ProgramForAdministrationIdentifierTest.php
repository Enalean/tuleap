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

namespace Tuleap\ProgramManagement\Domain\Program\Admin;

use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Tests\Stub\ProjectIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyProjectPermissionStub;

final class ProgramForAdministrationIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private VerifyIsTeamStub $team_verifier;
    private VerifyProjectPermissionStub $permission_verifier;
    private ProjectIdentifierStub $project;
    private UserIdentifierStub $user;
    private RetrieveUserStub $retrieve_user;

    protected function setUp(): void
    {
        $this->project             = ProjectIdentifierStub::build();
        $this->user                = UserIdentifierStub::buildGenericUser();
        $this->team_verifier       = VerifyIsTeamStub::withNotValidTeam();
        $this->permission_verifier = VerifyProjectPermissionStub::withAdministrator();
        $this->retrieve_user       = RetrieveUserStub::withGenericUser();
    }

    public function testItBuildsFromAProject(): void
    {
        $program = ProgramForAdministrationIdentifier::fromProject(
            $this->team_verifier,
            $this->permission_verifier,
            $this->retrieve_user,
            $this->user,
            $this->project
        );
        self::assertSame(101, $program->id);
    }

    public function testItThrowsWhenProjectIsATeam(): void
    {
        $this->team_verifier = VerifyIsTeamStub::withValidTeam();

        $this->expectException(ProgramCannotBeATeamException::class);
        ProgramForAdministrationIdentifier::fromProject(
            $this->team_verifier,
            $this->permission_verifier,
            $this->retrieve_user,
            $this->user,
            $this->project
        );
    }

    public function testItThrowsWhenUserIsNotProjectAdministrator(): void
    {
        $this->permission_verifier = VerifyProjectPermissionStub::withNotAdministrator();

        $this->expectException(ProgramAccessException::class);
        ProgramForAdministrationIdentifier::fromProject(
            $this->team_verifier,
            $this->permission_verifier,
            $this->retrieve_user,
            $this->user,
            $this->project
        );
    }
}
