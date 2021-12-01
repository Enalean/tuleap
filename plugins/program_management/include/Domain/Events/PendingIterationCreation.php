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

namespace Tuleap\ProgramManagement\Domain\Events;

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ChangesetIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\DomainChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\VerifyIsChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIsIteration;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I hold an Iteration artifact id and one of its changeset id.
 * @psalm-immutable
 */
final class PendingIterationCreation
{
    private function __construct(private IterationIdentifier $iteration, private ChangesetIdentifier $changeset)
    {
    }

    public static function fromIds(
        VerifyIsIteration $iteration_verifier,
        VerifyIsVisibleArtifact $visibility_verifier,
        VerifyIsChangeset $changeset_verifier,
        int $iteration_id,
        int $changeset_id,
        UserIdentifier $user,
    ): ?self {
        $iteration = IterationIdentifier::fromId($iteration_verifier, $visibility_verifier, $iteration_id, $user);
        if (! $iteration) {
            return null;
        }
        $changeset = DomainChangeset::fromId($changeset_verifier, $changeset_id);
        if (! $changeset) {
            return null;
        }
        return new self($iteration, $changeset);
    }

    public function getIteration(): IterationIdentifier
    {
        return $this->iteration;
    }

    public function getChangeset(): ChangesetIdentifier
    {
        return $this->changeset;
    }
}
