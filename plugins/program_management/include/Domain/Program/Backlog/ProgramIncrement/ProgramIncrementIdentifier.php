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

use Tuleap\ProgramManagement\Adapter\Events\ArtifactUpdatedProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\VerifyIsProgramIncrementTracker;

/**
 * I am the ID (identifier) of an Artifact from the Program Increment tracker.
 * @psalm-immutable
 */
final class ProgramIncrementIdentifier
{
    private int $id;

    private function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @throws ProgramIncrementNotFoundException
     */
    public static function fromId(
        CheckProgramIncrement $check_program_increment,
        int $program_increment_id,
        \PFUser $user
    ): self {
        $check_program_increment->checkIsAProgramIncrement($program_increment_id, $user);

        return new self($program_increment_id);
    }

    public static function fromArtifactUpdated(
        VerifyIsProgramIncrementTracker $program_increment_verifier,
        ArtifactUpdatedProxy $artifact_updated
    ): ?self {
        if (! $program_increment_verifier->isProgramIncrementTracker($artifact_updated->tracker_id)) {
            return null;
        }
        return new self($artifact_updated->artifact_id);
    }
}
