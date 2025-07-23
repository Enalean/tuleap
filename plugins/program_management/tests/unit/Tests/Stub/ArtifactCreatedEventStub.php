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

use Tuleap\ProgramManagement\Domain\Events\ArtifactCreatedEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ChangesetIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\DomainChangeset;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Artifact\ArtifactIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * @psalm-immutable
 */
final class ArtifactCreatedEventStub implements ArtifactCreatedEvent
{
    private function __construct(
        private ArtifactIdentifier $artifact,
        private TrackerIdentifier $tracker,
        private UserIdentifier $user,
        private ChangesetIdentifier $changeset,
    ) {
    }

    public static function withIds(
        int $artifact_id,
        int $tracker_id,
        int $user_id,
        int $changeset_id,
    ): self {
        $changeset = DomainChangeset::fromId(VerifyIsChangesetStub::withValidChangeset(), $changeset_id);
        if (! $changeset) {
            throw new \LogicException('Changeset is not valid');
        }

        return new self(
            ArtifactIdentifierStub::withId($artifact_id),
            TrackerIdentifierStub::withId($tracker_id),
            UserIdentifierStub::withId($user_id),
            $changeset
        );
    }

    #[\Override]
    public function getArtifact(): ArtifactIdentifier
    {
        return $this->artifact;
    }

    #[\Override]
    public function getTracker(): TrackerIdentifier
    {
        return $this->tracker;
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
        return $this->changeset;
    }
}
