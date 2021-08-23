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

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\DeletePendingIterations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\SearchPendingIteration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\StorePendingIterations;

final class PendingIterationCreationDAO extends DataAccessObject implements StorePendingIterations, SearchPendingIteration, DeletePendingIterations
{
    public function storePendingIterationCreations(IterationCreation ...$creations): void
    {
        $creation_maps = array_map(static function (IterationCreation $creation): array {
            return [
                'iteration_id'           => $creation->iteration->id,
                'program_increment_id'   => $creation->program_increment->getId(),
                'user_id'                => $creation->user->getId(),
                'iteration_changeset_id' => $creation->changeset->getId()
            ];
        }, $creations);

        $this->getDB()->insertMany('plugin_program_management_pending_iterations', $creation_maps);
    }

    public function searchPendingIterationCreation(int $iteration_id, int $user_id): ?array
    {
        return $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($iteration_id, $user_id): ?array {
            $this->cleanupDeletedIterationsFromPendingStore($iteration_id);
            $this->cleanupDeletedProgramIncrementsFromPendingStore($iteration_id);
            return $db->row(
                'SELECT iteration_id, program_increment_id, user_id, iteration_changeset_id
                FROM plugin_program_management_pending_iterations
                WHERE iteration_id = ? AND user_id = ?',
                $iteration_id,
                $user_id
            );
        });
    }

    private function cleanupDeletedIterationsFromPendingStore(int $iteration_id): void
    {
        $sql = 'DELETE pending.*
                FROM plugin_program_management_pending_iterations AS pending
                LEFT JOIN tracker_artifact ON tracker_artifact.id = pending.iteration_id
                WHERE tracker_artifact.id IS NULL AND pending.iteration_id = ?';

        $this->getDB()->run($sql, $iteration_id);
    }

    private function cleanupDeletedProgramIncrementsFromPendingStore(int $program_increment_id): void
    {
        $sql = 'DELETE pending.*
                FROM plugin_program_management_pending_iterations AS pending
                LEFT JOIN tracker_artifact ON tracker_artifact.id = pending.program_increment_id
                WHERE tracker_artifact.id IS NULL AND pending.iteration_id = ?';

        $this->getDB()->run($sql, $program_increment_id);
    }

    public function deletePendingIterationCreationsByIterationId(int $iteration_id): void
    {
        $sql = 'DELETE pending.*
                FROM plugin_program_management_pending_iterations AS pending
                WHERE pending.iteration_id = ?';

        $this->getDB()->run($sql, $iteration_id);
    }

    public function deletePendingIterationCreationsByProgramIncrementId(int $program_increment_id): void
    {
        $sql = 'DELETE pending.*
                FROM plugin_program_management_pending_iterations AS pending
                WHERE pending.program_increment_id = ?';

        $this->getDB()->run($sql, $program_increment_id);
    }
}
