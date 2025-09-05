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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;

/**
 * I am a Changeset identifier that can be built in the Domain, without dependency
 * on Tracker objects.
 * @psalm-immutable
 */
final class DomainChangeset implements ChangesetIdentifier
{
    private int $id;

    private function __construct(int $id)
    {
        $this->id = $id;
    }

    public static function fromId(VerifyIsChangeset $changeset_verifier, int $changeset_id): ?self
    {
        if (! $changeset_verifier->isChangeset($changeset_id)) {
            return null;
        }
        return new self($changeset_id);
    }

    public static function fromIterationLastChangeset(
        RetrieveLastChangeset $changeset_retriever,
        IterationIdentifier $iteration,
    ): ?self {
        $changeset_id = $changeset_retriever->retrieveLastChangesetId($iteration);
        return ($changeset_id !== null) ? new self($changeset_id) : null;
    }

    #[\Override]
    public function getId(): int
    {
        return $this->id;
    }
}
