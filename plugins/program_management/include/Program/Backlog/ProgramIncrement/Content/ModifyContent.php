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

namespace Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Content;

use Tuleap\ProgramManagement\Adapter\Program\Tracker\ProgramTrackerException;
use Tuleap\ProgramManagement\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Program\ProgramNotFoundException;

/**
 * I add and/or reorder the contents of a Program Increment
 */
interface ModifyContent
{
    /**
     * @throws ProgramTrackerException
     * @throws ProgramIncrementNotFoundException
     * @throws ProgramNotFoundException
     * @throws NotAllowedToPrioritizeException
     */
    public function modifyContent(\PFUser $user, int $program_increment_id, ContentChange $content_change): void;
}
