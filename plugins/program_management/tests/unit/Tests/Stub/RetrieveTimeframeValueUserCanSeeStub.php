<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveTimeframeValueUserCanSee;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Artifact\ArtifactIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class RetrieveTimeframeValueUserCanSeeStub implements RetrieveTimeframeValueUserCanSee
{
    private function __construct(private int $start_date, private int $end_date)
    {
    }

    public static function withValues(int $start_date, int $end_date): self
    {
        return new self($start_date, $end_date);
    }

    #[\Override]
    public function getStartDateValueTimestamp(ArtifactIdentifier $artifact_identifier, UserIdentifier $user_identifier): ?int
    {
        return $this->start_date;
    }

    #[\Override]
    public function getEndDateValueTimestamp(ArtifactIdentifier $artifact_identifier, UserIdentifier $user_identifier): ?int
    {
        return $this->end_date;
    }
}
