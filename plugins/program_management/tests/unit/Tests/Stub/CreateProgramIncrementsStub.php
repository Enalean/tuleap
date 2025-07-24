<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\CreateProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredProgramIncrementTrackerIdentifierCollection;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class CreateProgramIncrementsStub implements CreateProgramIncrements
{
    private function __construct(private int $calls_count)
    {
    }

    public static function build(): self
    {
        return new self(0);
    }

    #[\Override]
    public function createProgramIncrements(
        SourceTimeboxChangesetValues $values,
        MirroredProgramIncrementTrackerIdentifierCollection $mirrored_trackers,
        UserIdentifier $user_identifier,
    ): void {
        $this->calls_count++;
    }

    public function getCallsCount(): int
    {
        return $this->calls_count;
    }
}
