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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\PendingArtifactCreationStore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\StoreProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;

final class PendingProgramIncrementCreationDAO extends DataAccessObject implements PendingArtifactCreationStore, StoreProgramIncrementCreation
{
    public function getPendingArtifactById(int $artifact_id, int $user_id): ?array
    {
        return $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($artifact_id, $user_id): ?array {
            $this->deleteNonExistentArtifactFromPendingCreation($artifact_id);
            return $db->row(
                'SELECT program_artifact_id, user_id, changeset_id FROM plugin_program_management_pending_mirrors WHERE program_artifact_id = ? AND user_id = ?',
                $artifact_id,
                $user_id
            );
        });
    }

    public function storeCreation(ProgramIncrementCreation $creation): void
    {
        $this->getDB()->insert('plugin_program_management_pending_mirrors', [
            'program_artifact_id' => $creation->getProgramIncrement()->getId(),
            'user_id'             => $creation->getUser()->getId(),
            'changeset_id'        => $creation->getChangeset()->getId()
        ]);
    }

    public function deleteArtifactFromPendingCreation(int $artifact_id, int $user_id): void
    {
        $this->getDB()->run(
            "DELETE FROM plugin_program_management_pending_mirrors
            WHERE program_artifact_id = ? AND user_id = ?",
            $artifact_id,
            $user_id
        );
    }

    private function deleteNonExistentArtifactFromPendingCreation(int $artifact_id): void
    {
        $sql = 'DELETE plugin_program_management_pending_mirrors.*
                FROM plugin_program_management_pending_mirrors
                LEFT JOIN tracker_artifact ON (tracker_artifact.id = plugin_program_management_pending_mirrors.program_artifact_id)
                WHERE tracker_artifact.id IS NULL AND plugin_program_management_pending_mirrors.program_artifact_id = ?';

        $this->getDB()->run($sql, $artifact_id);
    }
}
