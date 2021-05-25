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

namespace Tuleap\ProgramManagement\Stub;

use Tuleap\ProgramManagement\Domain\Program\Plan\BuildTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramPlannableTracker;
use Tuleap\ProgramManagement\Domain\Program\PlanTrackerNotFoundException;

final class BuildTrackerStub implements BuildTracker
{
    /* @var bool */
    private $is_valid;
    /* @var bool */
    private $return_tracker_list;

    private function __construct(bool $is_valid, bool $return_tracker_list)
    {
        $this->is_valid            = $is_valid;
        $this->return_tracker_list = $return_tracker_list;
    }

    public function buildPlannableTrackerList(array $plannable_trackers_id, int $project_id): array
    {
        if ($this->return_tracker_list || count($plannable_trackers_id) > 0) {
            return [ProgramPlannableTracker::build(
                $this,
                $plannable_trackers_id[0],
                $project_id
            )];
        }

        throw new Exception("Should not be called.");
    }

    public function checkTrackerIsValid(int $tracker_id, int $project_id): void
    {
        if (! $this->is_valid) {
            throw new PlanTrackerNotFoundException($tracker_id);
        }
    }

    public static function buildValidTracker(): self
    {
        return new self(true, false);
    }

    public static function buildTrackerNotValid(): self
    {
        return new self(false, false);
    }

    public static function buildTrackerIsValidAndGetPlannableTrackerList(): self
    {
        return new self(true, true);
    }
}
