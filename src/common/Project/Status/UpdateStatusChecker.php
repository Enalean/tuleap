<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Status;

use Project;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class UpdateStatusChecker
{
    /**
     * @return Ok<null> | Err<Fault>
     */
    public static function checkProjectStatusCanBeUpdated(Project $project, string $new_status): Ok|Err
    {
        if ($new_status === Project::STATUS_PENDING) {
            return Result::err(SwitchingBackToPendingFault::build());
        }

        if ($project->getStatus() === Project::STATUS_DELETED) {
            return Result::err(UpdateAlreadyDeletedProjectFault::build());
        }

        if ((int) $project->getID() === Project::DEFAULT_ADMIN_PROJECT_ID && $new_status === Project::STATUS_DELETED) {
            return Result::err(CannotDeletedDefaultAdminProjectFault::build());
        }

        return Result::ok(null);
    }
}
