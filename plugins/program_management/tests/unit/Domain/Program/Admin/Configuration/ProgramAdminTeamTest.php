<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirrorTimeboxesFromProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchOpenProgramIncrementsStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchVisibleTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsSynchronizationPendingStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyTeamSynchronizationHasErrorStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramAdminTeamTest extends TestCase
{
    private SearchOpenProgramIncrementsStub $open_program_increment_searcher;
    private \Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier $program_for_admin;
    private UserIdentifierStub $user_identifier;

    protected function setUp(): void
    {
        $program_increment                     = ProgramIncrementBuilder::buildWithId(222);
        $this->open_program_increment_searcher = SearchOpenProgramIncrementsStub::withProgramIncrements($program_increment);
        $this->program_for_admin               = ProgramForAdministrationIdentifierBuilder::buildWithId($program_increment->id);
        $this->user_identifier                 = UserIdentifierStub::buildGenericUser();
    }

    public function testBuildAPresenterForTeamWithConfigurationError(): void
    {
        $team       = ProjectReferenceStub::withId(150);
        $collection = TeamProjectsCollectionBuilder::withProjects(
            $team,
        );

        $teams_presenter = ProgramAdminTeam::build(
            $this->open_program_increment_searcher,
            SearchMirrorTimeboxesFromProgramStub::buildWithoutMissingMirror(),
            $this->program_for_admin,
            $this->user_identifier,
            $collection,
            VerifyIsSynchronizationPendingStub::withoutOnGoingSynchronization(),
            SearchVisibleTeamsOfProgramStub::withTeamIds($team->getId()),
            VerifyTeamSynchronizationHasErrorStub::buildWithoutError(),
            BuildProgramStub::stubValidProgram(),
            null,
            null,
            null,
        );
        self::assertSame($team->getId(), $teams_presenter[0]->id);
        self::assertFalse($teams_presenter[0]->should_synchronize_team);
    }

    public function testBuildPresenterWithSynchronizeButtonWhenTeamHasMissingMirror(): void
    {
        $team       = ProjectReferenceStub::withId(150);
        $collection = TeamProjectsCollectionBuilder::withProjects(
            $team,
        );

        $teams_presenter = ProgramAdminTeam::build(
            $this->open_program_increment_searcher,
            SearchMirrorTimeboxesFromProgramStub::buildWithMissingMirror(),
            $this->program_for_admin,
            $this->user_identifier,
            $collection,
            VerifyIsSynchronizationPendingStub::withoutOnGoingSynchronization(),
            SearchVisibleTeamsOfProgramStub::withTeamIds($team->getId()),
            VerifyTeamSynchronizationHasErrorStub::buildWithoutError(),
            BuildProgramStub::stubValidProgram(),
            null,
            null,
            null,
        );
        self::assertSame($team->getId(), $teams_presenter[0]->id);
        self::assertTrue($teams_presenter[0]->should_synchronize_team);
    }

    public function testBuildPresenterWithoutSynchronizeButtonWhenTeamHasNoMissingMirror(): void
    {
        $team       = ProjectReferenceStub::withId(150);
        $collection = TeamProjectsCollectionBuilder::withProjects(
            $team,
        );

        $teams_presenter = ProgramAdminTeam::build(
            $this->open_program_increment_searcher,
            SearchMirrorTimeboxesFromProgramStub::buildWithoutMissingMirror(),
            $this->program_for_admin,
            $this->user_identifier,
            $collection,
            VerifyIsSynchronizationPendingStub::withOnGoingSynchronization(),
            SearchVisibleTeamsOfProgramStub::withTeamIds($team->getId()),
            VerifyTeamSynchronizationHasErrorStub::buildWithoutError(),
            BuildProgramStub::stubValidProgram(),
            null,
            null,
            null,
        );
        self::assertSame($team->getId(), $teams_presenter[0]->id);
        self::assertFalse($teams_presenter[0]->should_synchronize_team);
    }
}
