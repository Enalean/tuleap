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
 * I hold the identifier of a Tracker that can be planned in a Program Increment.
 * @psalm-immutable
 */
final readonly class NewTrackerThatCanBePlanned
{
    private function __construct(public int $id)
    {
    }

    /**
     * @throws ProgramTrackerException
     */
    public static function fromId(
        CheckNewPlannableTracker $tracker_checker,
        int $tracker_id,
        ProgramForAdministrationIdentifier $program,
    ): self {
        $tracker_checker->checkPlannableTrackerIsValid($tracker_id, $program);
        return new self($tracker_id);
    }

    public static function fromValidTracker(NewConfigurationTrackerIsValidCertificate $certificate): self
    {
        return new self($certificate->tracker_id);
    }
}
