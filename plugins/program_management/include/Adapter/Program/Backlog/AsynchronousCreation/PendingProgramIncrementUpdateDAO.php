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
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\DeletePendingProgramIncrementUpdates;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\PendingProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\SearchPendingProgramIncrementUpdates;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\StoreProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;

final class PendingProgramIncrementUpdateDAO extends DataAccessObject implements StoreProgramIncrementUpdate, SearchPendingProgramIncrementUpdates, DeletePendingProgramIncrementUpdates
{
    public function storeUpdate(ProgramIncrementUpdate $update): void
    {
        $this->getDB()->insert('plugin_program_management_pending_program_increment_update', [
            'program_increment_id' => $update->program_increment->getId(),
            'user_id'              => $update->user->getId(),
            'changeset_id'         => $update->changeset->getId()
        ]);
    }

    public function searchUpdate(int $program_increment_id, int $user_id, int $changeset_id): ?PendingProgramIncrementUpdate
    {
        return $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($program_increment_id, $user_id, $changeset_id) {
            $this->cleanupDeletedProgramIncrementsFromPendingStore($program_increment_id);
            $sql = 'SELECT program_increment_id, user_id, changeset_id
                FROM plugin_program_management_pending_program_increment_update
                WHERE program_increment_id = ? AND user_id = ? AND changeset_id = ?';
            $row = $this->getDB()->row($sql, $program_increment_id, $user_id, $changeset_id);
            if ($row === null) {
                return null;
            }
            return new PendingProgramIncrementUpdateProxy(
                $row['program_increment_id'],
                $row['user_id'],
                $row['changeset_id']
            );
        });
    }

    private function cleanupDeletedProgramIncrementsFromPendingStore(int $program_increment_id): void
    {
        $sql = 'DELETE pending.*
                FROM plugin_program_management_pending_program_increment_update AS pending
                LEFT JOIN tracker_artifact ON tracker_artifact.id = pending.program_increment_id
                WHERE tracker_artifact.id IS NULL AND pending.program_increment_id = ?';

        $this->getDB()->run($sql, $program_increment_id);
    }

    public function deletePendingProgramIncrementUpdatesByProgramIncrementId(int $program_increment_id): void
    {
        $sql = 'DELETE pending.*
                FROM plugin_program_management_pending_program_increment_update AS pending
                WHERE pending.program_increment_id = ?';

        $this->getDB()->run($sql, $program_increment_id);
    }

    public function deletePendingProgramIncrementUpdate(ProgramIncrementUpdate $update): void
    {
        $sql = 'DELETE pending.*
                FROM plugin_program_management_pending_program_increment_update AS pending
                WHERE pending.program_increment_id = ?
                  AND pending.user_id = ?
                  AND pending.changeset_id = ?';

        $this->getDB()->run(
            $sql,
            $update->program_increment->getId(),
            $update->user->getId(),
            $update->changeset->getId()
        );
    }
}
