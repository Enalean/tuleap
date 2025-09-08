<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\BuildProgramIncrementInfo;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementInfo;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\RetrieveProgramIncrement;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class ProgramIncrementInfoBuilder implements BuildProgramIncrementInfo
{
    public function __construct(private RetrieveProgramIncrement $retrieve_program_increments)
    {
    }

    #[\Override]
    public function build(UserIdentifier $user_identifier, ProgramIncrementIdentifier $increment_identifier): ProgramIncrementInfo
    {
        $increment = $this->retrieve_program_increments->retrieveProgramIncrementById($user_identifier, $increment_identifier);

        if ($increment === null) {
            throw new ProgramIncrementNotFoundException($increment_identifier->getId());
        }

        $formatted_start_date = ($increment->start_date)
            ? date(dgettext('tuleap-program_management', 'M d'), $increment->start_date)
            : '';

        $formatted_end_date = ($increment->end_date)
            ? date(dgettext('tuleap-program_management', 'M d'), $increment->end_date)
            : '';

        return ProgramIncrementInfo::fromIncrementInfo(
            $increment->id,
            $increment->title,
            $formatted_start_date,
            $formatted_end_date
        );
    }
}
