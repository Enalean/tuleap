<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Events\ArtifactCreatedEvent;
use Tuleap\ProgramManagement\Domain\Events\ArtifactUpdatedEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\VerifyIsProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I am the ID (identifier) of an Artifact from the Program Increment tracker.
 * @psalm-immutable
 */
final class ProgramIncrementIdentifier implements TimeboxIdentifier
{
    private function __construct(private int $id)
    {
    }

    #[\Override]
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @throws ProgramIncrementNotFoundException
     */
    public static function fromId(
        VerifyIsProgramIncrement $program_increment_verifier,
        VerifyIsVisibleArtifact $visibility_verifier,
        int $artifact_id,
        UserIdentifier $user,
    ): self {
        if (
            ! $program_increment_verifier->isProgramIncrement($artifact_id)
            || ! $visibility_verifier->isVisible($artifact_id, $user)
        ) {
            throw new ProgramIncrementNotFoundException($artifact_id);
        }

        return new self($artifact_id);
    }

    public static function fromArtifactEvent(
        VerifyIsProgramIncrementTracker $program_increment_verifier,
        ArtifactUpdatedEvent|ArtifactCreatedEvent $event,
    ): ?self {
        if (! $program_increment_verifier->isProgramIncrementTracker($event->getTracker()->getId())) {
            return null;
        }
        return new self($event->getArtifact()->getId());
    }
}
