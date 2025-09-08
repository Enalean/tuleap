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
use Tuleap\ProgramManagement\Domain\Events\ArtifactUpdatedEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ChangesetIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Artifact\ArtifactIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;

/**
 * I am a proxy for ArtifactUpdated
 * @see ArtifactUpdated
 * @psalm-immutable
 */
final class ArtifactUpdatedProxy implements ArtifactUpdatedEvent
{
    private function __construct(
        private ArtifactIdentifier $artifact,
        private TrackerIdentifier $tracker,
        private UserIdentifier $user,
        private ChangesetIdentifier $changeset,
        private ChangesetIdentifier $old_changeset,
    ) {
    }

    public static function fromArtifactUpdated(ArtifactUpdated $artifact_updated): self
    {
        $full_artifact = $artifact_updated->getArtifact();
        $artifact      = ArtifactIdentifierProxy::fromArtifact($full_artifact);
        $tracker       = TrackerIdentifierProxy::fromTracker($full_artifact->getTracker());
        $user          = UserProxy::buildFromPFUser($artifact_updated->getUser());
        $changeset     = ChangesetProxy::fromChangeset($artifact_updated->getChangeset());
        $old_changeset = ChangesetProxy::fromChangeset($artifact_updated->getOldChangeset());
        return new self($artifact, $tracker, $user, $changeset, $old_changeset);
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
