<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Plan;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerException;

/**
 * I hold the identifier of a Tracker that is going to be saved as Program Increment Tracker
 * @psalm-immutable
 */
final class NewProgramIncrementTracker
{
    private function __construct(private int $id)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @throws ProgramTrackerException
     */
    public static function fromId(
        CheckNewProgramIncrementTracker $tracker_checker,
        int $tracker_id,
        ProgramForAdministrationIdentifier $program,
    ): self {
        $tracker_checker->checkProgramIncrementTrackerIsValid($tracker_id, $program);
        return new self($tracker_id);
    }
}
