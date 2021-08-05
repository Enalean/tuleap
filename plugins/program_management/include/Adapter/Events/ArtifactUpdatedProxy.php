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

use Tuleap\ProgramManagement\Domain\Events\ArtifactUpdatedEvent;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;

/**
 * I am a proxy to ArtifactUpdated. If it changes, I will protect classes that depend on me from changes and
 * I will be the only one to change.
 * @see ArtifactUpdated
 * @psalm-immutable
 */
final class ArtifactUpdatedProxy implements ArtifactUpdatedEvent
{
    private int $artifact_id;
    private int $tracker_id;
    private UserIdentifier $user;

    private function __construct(int $artifact_id, int $tracker_id, UserIdentifier $user)
    {
        $this->artifact_id = $artifact_id;
        $this->tracker_id  = $tracker_id;
        $this->user        = $user;
    }

    public static function fromArtifactUpdated(ArtifactUpdated $artifact_updated): self
    {
        $artifact        = $artifact_updated->getArtifact();
        $user_identifier = UserProxy::buildFromPFUser($artifact_updated->getUser());
        return new self($artifact->getId(), $artifact->getTrackerId(), $user_identifier);
    }

    public function getArtifactId(): int
    {
        return $this->artifact_id;
    }

    public function getTrackerId(): int
    {
        return $this->tracker_id;
    }

    public function getUser(): UserIdentifier
    {
        return $this->user;
    }
}
