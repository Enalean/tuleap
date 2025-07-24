<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\CheckNewPlannableTracker;
use Tuleap\ProgramManagement\Domain\Program\PlanTrackerDoesNotBelongToProjectException;
use Tuleap\ProgramManagement\Domain\Program\PlanTrackerNotFoundException;

final class CheckNewPlannableTrackerStub implements CheckNewPlannableTracker
{
    private function __construct(
        private bool $is_not_found,
        private bool $is_not_in_program,
    ) {
    }

    public static function withValidTracker(): self
    {
        return new self(false, false);
    }

    public static function withTrackerNotFound(): self
    {
        return new self(true, false);
    }

    public static function withTrackerNotPartOfProgram(): self
    {
        return new self(false, true);
    }

    #[\Override]
    public function checkPlannableTrackerIsValid(
        int $plannable_tracker_id,
        ProgramForAdministrationIdentifier $program,
    ): void {
        if ($this->is_not_found) {
            throw new PlanTrackerNotFoundException($plannable_tracker_id);
        }
        if ($this->is_not_in_program) {
            throw new PlanTrackerDoesNotBelongToProjectException($plannable_tracker_id, $program->id);
        }
    }
}
