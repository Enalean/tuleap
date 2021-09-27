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

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ArtifactCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\CreateArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\SubmissionDate;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class CreateArtifactStub implements CreateArtifact
{
    private function __construct(private int $call_count, private bool $should_throw)
    {
    }

    public static function withCount(): self
    {
        return new self(0, false);
    }

    public static function withError(): self
    {
        return new self(0, true);
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }

    public function create(
        TrackerReference $tracker,
        MirroredTimeboxChangesetValues $mirrored_program_increment_changeset,
        UserIdentifier $user_identifier,
        SubmissionDate $submission_date
    ): void {
        if ($this->should_throw) {
            throw new ArtifactCreationException();
        }
        $this->call_count++;
    }
}
