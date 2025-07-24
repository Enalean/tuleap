<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Events\IterationUpdateEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ChangesetIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\IterationIdentifierBuilder;

/**
 * @psalm-immutable
 */
final class IterationUpdateEventStub implements IterationUpdateEvent
{
    private function __construct(
        private IterationIdentifier $iteration,
        private UserIdentifier $user,
        private ChangesetIdentifier $changeset,
    ) {
    }

    public static function withDefaultValues(): self
    {
        $iteration = IterationIdentifierBuilder::buildWithId(10);
        $user      = UserIdentifierStub::buildGenericUser();
        $changeset = ChangesetIdentifierStub::withId(15);
        return new self($iteration, $user, $changeset);
    }

    public static function withDefinedValues(int $iteration_id, int $user_id, int $changeset_id): self
    {
        $iteration = IterationIdentifierBuilder::buildWithId($iteration_id);
        $user      = UserIdentifierStub::withId($user_id);
        $changeset = ChangesetIdentifierStub::withId($changeset_id);

        return new self($iteration, $user, $changeset);
    }

    #[\Override]
    public function getIteration(): IterationIdentifier
    {
        return $this->iteration;
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
}
