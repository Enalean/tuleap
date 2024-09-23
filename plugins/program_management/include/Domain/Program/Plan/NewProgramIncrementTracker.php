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
 * I hold the identifier of a Tracker that is going to be saved as Program Increment Tracker.
 * I also hold its customized label and sub-label (if any).
 * @psalm-immutable
 */
final readonly class NewProgramIncrementTracker
{
    private function __construct(public int $id, public ?string $label, public ?string $sub_label)
    {
    }

    /**
     * @throws ProgramTrackerException
     */
    public static function fromProgramIncrementChange(
        CheckNewProgramIncrementTracker $tracker_checker,
        PlanProgramIncrementChange $program_increment_change,
        ProgramForAdministrationIdentifier $program,
    ): self {
        $tracker_checker->checkProgramIncrementTrackerIsValid($program_increment_change->tracker_id, $program);
        return new self(
            $program_increment_change->tracker_id,
            $program_increment_change->label,
            $program_increment_change->sub_label
        );
    }

    public static function fromValidTrackerAndLabels(
        NewConfigurationTrackerIsValidCertificate $certificate,
        ?string $label,
        ?string $sub_label,
    ): self {
        return new self($certificate->tracker_id, $label, $sub_label);
    }
}
