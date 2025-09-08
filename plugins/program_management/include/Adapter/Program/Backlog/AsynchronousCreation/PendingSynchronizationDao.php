<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ClearPendingTeamSynchronization;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\StorePendingTeamSynchronization;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\VerifyIsSynchronizationPending;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TeamSynchronization\CleanPendingSynchronizationDaily;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TeamSynchronization\StoreTeamSynchronizationErrorHasOccurred;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TeamSynchronization\VerifyTeamSynchronizationHasError;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifier;

final class PendingSynchronizationDao extends DataAccessObject implements VerifyIsSynchronizationPending, StorePendingTeamSynchronization, ClearPendingTeamSynchronization, CleanPendingSynchronizationDaily, StoreTeamSynchronizationErrorHasOccurred, VerifyTeamSynchronizationHasError
{
    #[\Override]
    public function hasSynchronizationPending(ProgramIdentifier $program_identifier, TeamIdentifier $team_identifier): bool
    {
        $sql = '
            SELECT 1
            FROM plugin_program_management_team_synchronizations_pending
            WHERE program_id = ?
              AND team_id = ?
        ';

        return $this->getDB()->exists($sql, $program_identifier->getId(), $team_identifier->getId());
    }

    #[\Override]
    public function storePendingTeamSynchronization(ProgramIdentifier $program_identifier, TeamIdentifier $team_identifier): void
    {
        $this->getDB()->insert(
            'plugin_program_management_team_synchronizations_pending',
            [
                'program_id' => $program_identifier->getId(),
                'team_id' => $team_identifier->getId(),
                'timestamp' => time(),
                'has_error' => false,
            ]
        );
    }

    #[\Override]
    public function storeErrorHasOccurred(int $program_id, int $team_id): void
    {
        $this->getDB()->update(
            'plugin_program_management_team_synchronizations_pending',
            ['has_error' => true],
            [
                'program_id' => $program_id,
                'team_id' => $team_id,
            ]
        );
    }

    #[\Override]
    public function clearPendingTeamSynchronisation(ProgramIdentifier $program_identifier, TeamIdentifier $team_identifier): void
    {
        $this->getDB()->delete(
            'plugin_program_management_team_synchronizations_pending',
            [
                'program_id' => $program_identifier->getId(),
                'team_id' => $team_identifier->getId(),
            ]
        );
    }

    #[\Override]
    public function dailyClean(int $timestamp): void
    {
        $sql = 'DELETE FROM plugin_program_management_team_synchronizations_pending WHERE timestamp < ?';

        $this->getDB()->run($sql, $timestamp);
    }

    #[\Override]
    public function hasASynchronizationError(ProgramIdentifier $program_identifier, ProjectReference $team_identifier): bool
    {
        $sql = '
            SELECT has_error
            FROM plugin_program_management_team_synchronizations_pending
            WHERE program_id = ?
              AND team_id = ?
        ';

        return (bool) $this->getDB()->single($sql, [$program_identifier->getId(), $team_identifier->getId()]);
    }
}
