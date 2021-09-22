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

use Tuleap\ProgramManagement\Adapter\Workspace\ArtifactIdentifierProxy;
use Tuleap\ProgramManagement\Domain\Events\ArtifactUpdatedEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ChangesetIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\DomainChangeset;
use Tuleap\ProgramManagement\Domain\Workspace\ArtifactIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\TrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class ArtifactUpdatedEventStub implements ArtifactUpdatedEvent
{
    private function __construct(
        private ArtifactIdentifier $artifact,
        private TrackerIdentifier $tracker,
        private UserIdentifier $user,
        private ChangesetIdentifier $changeset
    ) {
    }

    public static function withIds(int $artifact_id, TrackerIdentifier $tracker, UserIdentifier $user, int $changeset_id): self
    {
        $artifact = ArtifactTestBuilder::anArtifact($artifact_id)->build();
        return new self(
            ArtifactIdentifierProxy::fromArtifact($artifact),
            $tracker,
            $user,
            DomainChangeset::fromId(VerifyIsChangesetStub::withValidChangeset(), $changeset_id)
        );
    }

    public function getArtifact(): ArtifactIdentifier
    {
        return $this->artifact;
    }

    public function getTracker(): TrackerIdentifier
    {
        return $this->tracker;
    }

    public function getUser(): UserIdentifier
    {
        return $this->user;
    }

    public function getChangeset(): ChangesetIdentifier
    {
        return $this->changeset;
    }
}
