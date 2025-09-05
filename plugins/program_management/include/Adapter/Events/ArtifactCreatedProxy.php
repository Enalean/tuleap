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

namespace Tuleap\ProgramManagement\Adapter\Events;

use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\ChangesetProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\ArtifactIdentifierProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerIdentifierProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Events\ArtifactCreatedEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ChangesetIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Artifact\ArtifactIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;

/**
 * I am a proxy for ArtifactCreated.
 * @see ArtifactCreated
 * @psalm-immutable
 */
final class ArtifactCreatedProxy implements ArtifactCreatedEvent
{
    private function __construct(
        private ArtifactIdentifier $artifact,
        private TrackerIdentifier $tracker,
        private UserIdentifier $user,
        private ChangesetIdentifier $changeset,
        private ChangesetIdentifier $old_changeset,
    ) {
    }

    public static function fromArtifactCreated(ArtifactCreated $artifact_created): self
    {
        $full_artifact = $artifact_created->getArtifact();
        $artifact      = ArtifactIdentifierProxy::fromArtifact($full_artifact);
        $tracker       = TrackerIdentifierProxy::fromTracker($full_artifact->getTracker());
        $user          = UserProxy::buildFromPFUser($artifact_created->getUser());
        $changeset     = ChangesetProxy::fromChangeset($artifact_created->getChangeset());
        return new self($artifact, $tracker, $user, $changeset, $changeset);
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
        return $this->old_changeset;
    }
}
