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
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\PendingIterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\SearchPendingIterations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\StoreIterationCreations;

final class PendingIterationCreationDAO extends DataAccessObject implements StoreIterationCreations, SearchPendingIterations, DeletePendingIterations
{
    public function storeCreations(IterationCreation ...$creations): void
    {
        if (empty($creations)) {
            return;
        }
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

    public function searchIterationCreationsByProgramIncrement(int $program_increment_id, int $user_id): array
    {
        return $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($program_increment_id, $user_id): array {
            $this->cleanupDeletedIterationsFromPendingStore($program_increment_id);
            $this->cleanupDeletedProgramIncrementsFromPendingStore($program_increment_id);
            $rows = $db->q(
                'SELECT iteration_id, program_increment_id, user_id, iteration_changeset_id
                FROM plugin_program_management_pending_iterations
                WHERE program_increment_id = ? AND user_id = ?',
                $program_increment_id,
                $user_id
            );
            return array_map(
                static fn(array $row): PendingIterationCreation => new PendingIterationCreationProxy(
                    $row['iteration_id'],
                    $row['program_increment_id'],
                    $row['user_id'],
                    $row['iteration_changeset_id']
                ),
                $rows
            );
        });
    }

    private function cleanupDeletedIterationsFromPendingStore(int $program_increment_id): void
    {
        $sql = 'DELETE pending.*
                FROM plugin_program_management_pending_iterations AS pending
                LEFT JOIN tracker_artifact ON tracker_artifact.id = pending.iteration_id
                WHERE tracker_artifact.id IS NULL AND pending.program_increment_id = ?';

        $this->getDB()->run($sql, $program_increment_id);
    }

    private function cleanupDeletedProgramIncrementsFromPendingStore(int $program_increment_id): void
    {
        $sql = 'DELETE pending.*
                FROM plugin_program_management_pending_iterations AS pending
                LEFT JOIN tracker_artifact ON tracker_artifact.id = pending.program_increment_id
                WHERE tracker_artifact.id IS NULL AND pending.program_increment_id = ?';

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
