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

namespace Tuleap\ProgramManagement\Domain\Team\MirroredTimebox;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TeamIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementFromTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirroredTimeboxesStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;

final class MirroredProgramIncrementIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_MIRROR_ID  = 48;
    private const SECOND_MIRROR_ID = 37;
    private SearchMirroredTimeboxesStub $timebox_searcher;
    private VerifyIsVisibleArtifactStub $visibility_verifier;
    private RetrieveMirroredProgramIncrementFromTeamStub $mirror_retriever;
    private ProgramIncrementIdentifier $program_increment;
    private UserIdentifierStub $user;

    protected function setUp(): void
    {
        $this->timebox_searcher    = SearchMirroredTimeboxesStub::withIds(
            self::FIRST_MIRROR_ID,
            self::SECOND_MIRROR_ID
        );
        $this->mirror_retriever    = RetrieveMirroredProgramIncrementFromTeamStub::withIds(self::FIRST_MIRROR_ID);
        $this->visibility_verifier = VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts();

        $this->user              = UserIdentifierStub::buildGenericUser();
        $this->program_increment = ProgramIncrementIdentifierBuilder::buildWithId(11);
    }

    private function getFromProgramIncrementAndTeam(): ?MirroredProgramIncrementIdentifier
    {
        return MirroredProgramIncrementIdentifier::fromProgramIncrementAndTeam(
            $this->mirror_retriever,
            $this->visibility_verifier,
            $this->program_increment,
            TeamIdentifierBuilder::build(),
            $this->user
        );
    }

    public function testItBuildsFromProgramIncrementAndTeam(): void
    {
        $mirror = $this->getFromProgramIncrementAndTeam();
        self::assertNotNull($mirror);
        self::assertSame(self::FIRST_MIRROR_ID, $mirror->getId());
    }

    public function testItReturnsNullWhenNoMirrorFound(): void
    {
        $this->mirror_retriever = RetrieveMirroredProgramIncrementFromTeamStub::withNoMirror();
        self::assertNull($this->getFromProgramIncrementAndTeam());
    }

    public function testItReturnsNullWhenMirrorIsNotVisible(): void
    {
        $this->visibility_verifier = VerifyIsVisibleArtifactStub::withNoVisibleArtifact();
        self::assertNull($this->getFromProgramIncrementAndTeam());
    }

    public function testItBuildsCollectionFromProgramIncrement(): void
    {
        $identifiers = MirroredProgramIncrementIdentifier::buildCollectionFromProgramIncrement(
            $this->timebox_searcher,
            $this->visibility_verifier,
            $this->program_increment,
            $this->user
        );
        $ids         = array_map(
            static fn(MirroredProgramIncrementIdentifier $identifier) => $identifier->getId(),
            $identifiers
        );
        self::assertContains(self::FIRST_MIRROR_ID, $ids);
        self::assertContains(self::SECOND_MIRROR_ID, $ids);
    }

    public function testItBuildsEmptyArrayWhenProgramIncrementHasNoMirror(): void
    {
        self::assertEmpty(
            MirroredProgramIncrementIdentifier::buildCollectionFromProgramIncrement(
                SearchMirroredTimeboxesStub::withNoMirrors(),
                $this->visibility_verifier,
                $this->program_increment,
                $this->user
            )
        );
    }

    public function testItSkipsMirrorsThatAreNotVisible(): void
    {
        $identifiers = MirroredProgramIncrementIdentifier::buildCollectionFromProgramIncrement(
            $this->timebox_searcher,
            VerifyIsVisibleArtifactStub::withVisibleIds(self::SECOND_MIRROR_ID),
            $this->program_increment,
            $this->user
        );
        $ids         = array_map(
            static fn(MirroredProgramIncrementIdentifier $identifier) => $identifier->getId(),
            $identifiers
        );
        self::assertNotContains(self::FIRST_MIRROR_ID, $ids);
        self::assertContains(self::SECOND_MIRROR_ID, $ids);
    }
}
