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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Events\PendingIterationCreation;
use Tuleap\ProgramManagement\Domain\Events\ProgramIncrementUpdateEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ChangesetIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;

/**
 * @psalm-immutable
 */
final class ProgramIncrementUpdateEventStub implements ProgramIncrementUpdateEvent
{
    /**
     * @var PendingIterationCreation[]
     */
    private array $iterations;

    private function __construct(
        private ProgramIncrementIdentifier $program_increment,
        private UserIdentifier $user,
        private ChangesetIdentifier $changeset,
        private ChangesetIdentifier $old_changeset,
        PendingIterationCreation ...$iterations,
    ) {
        $this->iterations = $iterations;
    }

    public static function withIds(
        int $program_increment_id,
        int $user_id,
        int $changeset_id,
        int $old_changeset_id,
        PendingIterationCreation ...$iterations,
    ): self {
        $user              = UserIdentifierStub::withId($user_id);
        $program_increment = ProgramIncrementIdentifierBuilder::buildWithIdAndUser($program_increment_id, $user);
        $changeset         = ChangesetIdentifierStub::withId($changeset_id);
        $old_changeset     = ChangesetIdentifierStub::withId($old_changeset_id);
        return new self($program_increment, $user, $changeset, $old_changeset, ...$iterations);
    }

    public static function withNoIterations(int $program_increment_id, int $user_id, int $changeset_id, int $old_changeset_id): self
    {
        $user              = UserIdentifierStub::withId($user_id);
        $program_increment = ProgramIncrementIdentifierBuilder::buildWithIdAndUser($program_increment_id, $user);
        $changeset         = ChangesetIdentifierStub::withId($changeset_id);
        $old_changeset     = ChangesetIdentifierStub::withId($changeset_id);
        return new self($program_increment, $user, $changeset, $old_changeset);
    }

    #[\Override]
    public function getProgramIncrement(): ProgramIncrementIdentifier
    {
        return $this->program_increment;
    }

    #[\Override]
    public function getUser(): UserIdentifier
    {
        return $this->user;
    }

    #[\Override]
    public function getChangeset(): ChangesetIdentifier
    {
        return $this->changeset;
    }

    #[\Override]
    public function getOldChangeset(): ChangesetIdentifier
    {
        return $this->old_changeset;
    }

    #[\Override]
    public function getIterations(): array
    {
        return $this->iterations;
    }
}
