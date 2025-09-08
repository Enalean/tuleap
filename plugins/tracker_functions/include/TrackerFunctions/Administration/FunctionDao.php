<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\TrackerFunctions\Administration;

use Tuleap\DB\DataAccessObject;

final class FunctionDao extends DataAccessObject implements CheckFunctionIsActivated, UpdateFunctionActivation
{
    #[\Override]
    public function isFunctionActivated(int $tracker_id): bool
    {
        $config = $this->getDB()->cell(
            <<<EOSQL
            SELECT is_activated
            FROM plugin_tracker_functions
            WHERE tracker_id = ?
            EOSQL,
            $tracker_id
        );

        return $config === 1;
    }

    #[\Override]
    public function deactivateFunction(int $tracker_id): void
    {
        $this->getDB()
            ->run(
                <<<EOSQL
                INSERT INTO plugin_tracker_functions(tracker_id, is_activated)
                SELECT ?, 0
                ON DUPLICATE KEY UPDATE is_activated = 0
                EOSQL,
                $tracker_id
            );
    }

    #[\Override]
    public function activateFunction(int $tracker_id): void
    {
        $this->getDB()
            ->run(
                <<<EOSQL
                INSERT INTO plugin_tracker_functions(tracker_id, is_activated)
                SELECT ?, 1
                ON DUPLICATE KEY UPDATE is_activated = 1
                EOSQL,
                $tracker_id
            );
    }
}
