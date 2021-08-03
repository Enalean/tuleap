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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\NewPendingIterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\StorePendingIterations;

final class PendingIterationCreationDAO extends DataAccessObject implements StorePendingIterations
{
    public function storePendingIterationCreations(NewPendingIterationCreation ...$creations): void
    {
        $creation_maps = array_map(static function (NewPendingIterationCreation $creation): array {
            return [
                'iteration_id'           => $creation->iteration->id,
                'program_increment_id'   => $creation->program_increment->getId(),
                'user_id'                => $creation->user->user_id,
                'iteration_changeset_id' => $creation->changeset->id
            ];
        }, $creations);

        $this->getDB()->insertMany('plugin_program_management_pending_iterations', $creation_maps);
    }
}
