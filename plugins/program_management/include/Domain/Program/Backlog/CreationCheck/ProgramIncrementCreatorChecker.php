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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck;

use PFUser;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Project;

class ProgramIncrementCreatorChecker
{
    private TimeboxCreatorChecker $timebox_creator_checker;
    private VerifyIsProgramIncrementTracker $verify_is_program_increment;

    public function __construct(
        TimeboxCreatorChecker $timebox_creator_checker,
        VerifyIsProgramIncrementTracker $verify_is_program_increment
    ) {
        $this->timebox_creator_checker     = $timebox_creator_checker;
        $this->verify_is_program_increment = $verify_is_program_increment;
    }

    public function canCreateAProgramIncrement(PFUser $user, ProgramTracker $tracker, Project $project): bool
    {
        if (! $this->verify_is_program_increment->isProgramIncrementTracker($tracker->getTrackerId())) {
            return true;
        }

        return $this->timebox_creator_checker->canTimeboxBeCreated($tracker, $project, $user);
    }
}
