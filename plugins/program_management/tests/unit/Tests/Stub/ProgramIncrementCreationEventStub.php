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

use Tuleap\ProgramManagement\Domain\Events\ProgramIncrementCreationEvent;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * @psalm-immutable
 */
final class ProgramIncrementCreationEventStub implements ProgramIncrementCreationEvent
{
    private function __construct(private int $artifact_id, private UserIdentifier $user, private int $changeset_id)
    {
    }

    public static function withIds(int $artifact_id, int $user_id, int $changeset_id): self
    {
        return new self($artifact_id, UserReferenceStub::withIdAndName($user_id, 'Harold Goodpaster'), $changeset_id);
    }

    #[\Override]
    public function getArtifactId(): int
    {
        return $this->artifact_id;
    }

    #[\Override]
    public function getUser(): UserIdentifier
    {
        return $this->user;
    }

    #[\Override]
    public function getChangesetId(): int
    {
        return $this->changeset_id;
    }
}
